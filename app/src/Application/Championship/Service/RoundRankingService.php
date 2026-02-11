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
            $login = $registration->getPlayer()->getLogin();
            $availabilityMap[$login] = [
                'availableSemiFinal' => $registration->isAvailableSemiFinal1() || $registration->isAvailableSemiFinal2(),
                'availableFinal' => $registration->isAvailableFinal(),
            ];
        }

        foreach ($maps as $map) {
            if (!$map->getUid()) {
                continue;
            }

            // Get records for this map, filtered by round and laps
            $records = $this->getMapRecordsForRanking($map->getUid(), $round->getId(), $laps);

            if (empty($records)) {
                continue;
            }

            // Sort by time ASC
            usort($records, fn($a, $b) => $a['record']->getTime() <=> $b['record']->getTime());

            // Find best lap time holder for this map (bonus 10 pts)
            $bestLapHolder = $this->getBestLapHolder($map->getUid(), $round->getId());

            $totalPlayers = count($records);
            $position = 1;

            foreach ($records as $data) {
                $login = $data['record']->getPlayerLogin();

                // Calculate points: 1 point per player behind + 1 for self
                $points = $totalPlayers - $position + 1;

                // Calculate bonus for top 5
                $bonus = 0;
                if ($position <= 5) {
                    $bonus = (5 - $position) * 2;
                }

                // Best lap bonus: 10 pts
                if ($login === $bestLapHolder) {
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
                $playerStats[$login]['nbMaps']++;

                $position++;
            }
        }

        // Sort by nbMaps DESC, then total points DESC
        uasort($playerStats, function ($a, $b) {
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
            $availability = $availabilityMap[$login] ?? ['availableSemiFinal' => false, 'availableFinal' => false];
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
            $position++;
        }

        return $ranking;
    }

    private function getMapRecordsForRanking(string $mapUid, int $roundId, int $laps): array
    {
        $allRecords = $this->mapRecordRepository->findByMapUidWithPlayer($mapUid, $roundId);

        // Filter by laps and game mode (Laps = 3 for qualification)
        return array_filter($allRecords, function ($data) use ($laps) {
            $record = $data['record'];
            return $record->getLaps() === $laps && $record->getGameMode() === GameMode::Laps;
        });
    }

    private function getBestLapHolder(string $mapUid, int $roundId): ?string
    {
        $allRecords = $this->mapRecordRepository->findByMapUidWithPlayer($mapUid, $roundId);

        // Filter by laps = 1 (single lap) and game mode Laps
        $lapRecords = array_filter($allRecords, function ($data) {
            $record = $data['record'];
            return $record->getLaps() === 1 && $record->getGameMode() === GameMode::Laps;
        });

        if (empty($lapRecords)) {
            return null;
        }

        // Sort by time ASC and return the best
        usort($lapRecords, fn($a, $b) => $a['record']->getTime() <=> $b['record']->getTime());

        return $lapRecords[0]['record']->getPlayerLogin();
    }
}
