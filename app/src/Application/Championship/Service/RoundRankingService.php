<?php

declare(strict_types=1);

namespace App\Application\Championship\Service;

use App\Domain\Championship\Entity\Phase;
use App\Domain\Championship\Entity\Round;
use App\Domain\Championship\Enum\GameMode;
use App\Domain\Championship\Repository\MapRecordRepositoryInterface;
use App\Domain\Championship\Repository\RoundRegistrationRepositoryInterface;

final readonly class RoundRankingService implements RoundRankingServiceInterface
{
    public function __construct(
        private MapRecordRepositoryInterface $mapRecordRepository,
        private RoundRegistrationRepositoryInterface $registrationRepository,
    ) {
    }

    public function calculateQualificationRanking(Round $round, Phase $phase): array
    {
        $maps = $round->getMaps();
        $laps = $phase->getEffectiveLaps();
        $playerStats = []; // login => ['points' => int, 'bonus' => int, 'nbMaps' => int]

        // Build availability map from registrations
        $registrations = $this->registrationRepository->findByRound($round);
        $availabilityMap = [];

        foreach ($registrations as $registration) {
            $login = mb_strtolower($registration->getPlayer()->getLogin());
            $availabilityMap[$login] = [
                'availableSemiFinal' => $registration->isAvailableSemiFinal1() || $registration->isAvailableSemiFinal2(),
                'availableFinal' => $registration->isAvailableFinal(),
            ];
        }

        foreach ($maps as $map) {
            if (!$map->getUid()) {
                continue;
            }

            // Fetch every Laps record for this map once. Records are scoped to the
            // current qualif phase (phase_id = qualif.id OR NULL for legacy rows),
            // so semis / finale records on the same map don't pollute the ranking.
            $allRecords = $this->mapRecordRepository->findByMapUidWithPlayer(
                $map->getUid(),
                $round->getId(),
                $phase->getId(),
            );

            // Multi-lap records (5 laps by default) feed the main ranking.
            $records = array_filter($allRecords, static function (array $data) use ($laps): bool {
                $record = $data['record'];

                return $record->getGameMode() === GameMode::Laps
                    && $record->getLaps() === $laps;
            });

            if ($records === []) {
                continue;
            }

            usort($records, fn (array $a, array $b): int => $a['record']->getTime() <=> $b['record']->getTime());

            // Best single-lap holder for this map (bonus +10).
            $bestLapHolder = null;

            foreach ($allRecords as $data) {
                $record = $data['record'];

                if ($record->getGameMode() !== GameMode::Laps || $record->getLaps() !== 1) {
                    continue;
                }
                if ($bestLapHolder === null || $record->getTime() < $bestLapHolder['time']) {
                    $bestLapHolder = ['login' => $record->getPlayerLogin(), 'time' => $record->getTime()];
                }
            }

            $totalPlayers = \count($records);
            $position = 1;

            foreach ($records as $data) {
                $login = $data['record']->getPlayerLogin();

                $points = $totalPlayers - $position + 1;
                $bonus = 0;

                if ($position <= 5) {
                    $bonus = (5 - $position) * 2;
                }

                if ($bestLapHolder !== null && $login === $bestLapHolder['login']) {
                    $bonus += 10;
                }

                if (!isset($playerStats[$login])) {
                    $playerStats[$login] = [
                        'points' => 0,
                        'bonus' => 0,
                        'nbMaps' => 0,
                        'playerPseudo' => $data['playerPseudo'],
                    ];
                }

                $playerStats[$login]['points'] += $points;
                $playerStats[$login]['bonus'] += $bonus;
                ++$playerStats[$login]['nbMaps'];

                ++$position;
            }
        }

        // Sort by nbMaps DESC, then total points DESC
        uasort($playerStats, function (array $a, array $b): int {
            $totalA = $a['points'] + $a['bonus'];
            $totalB = $b['points'] + $b['bonus'];

            if ($a['nbMaps'] !== $b['nbMaps']) {
                return $b['nbMaps'] <=> $a['nbMaps'];
            }

            return $totalB <=> $totalA;
        });

        // Build final ranking with positions
        $ranking = [];
        $position = 1;

        foreach ($playerStats as $login => $stats) {
            $availability = $availabilityMap[mb_strtolower($login)] ?? ['availableSemiFinal' => false, 'availableFinal' => false];
            $ranking[] = [
                'position' => $position,
                'login' => $login,
                'pseudo' => $stats['playerPseudo'] ?? $login,
                'points' => $stats['points'],
                'bonus' => $stats['bonus'],
                'total' => $stats['points'] + $stats['bonus'],
                'nbMaps' => $stats['nbMaps'],
                'availableSemiFinal' => $availability['availableSemiFinal'],
                'availableFinal' => $availability['availableFinal'],
            ];
            ++$position;
        }

        return $ranking;
    }

}
