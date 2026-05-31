<?php

declare(strict_types=1);

namespace App\Application\Championship\Service;

use App\Domain\Championship\Entity\Phase;
use App\Domain\Championship\Entity\PhaseType;
use App\Domain\Championship\Entity\Round;
use App\Domain\Championship\Entity\RoundMap;
use App\Domain\Championship\Enum\GameMode;
use App\Domain\Championship\Repository\MapRecordRepositoryInterface;
use App\Domain\Championship\Repository\PhaseRepositoryInterface;
use App\Domain\Championship\Repository\PhaseResultRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Computes the individual final ranking of a Round's Final phase from the
 * raw map_record rows (laps = phase effective laps, gameMode = Laps, scoped to
 * the finale phase_id). Mirrors the legacy schema:
 *
 *   ranking[i] = {
 *     position, login, pseudo,
 *     maps: { mapId: {place, temps} },
 *     bq: bonus qualif (totalQualPlayers + nbMaps - qualifPosition),
 *     bd: bonus demi   (10 if qualified to finale via a semi, else 0),
 *     bf: bonus finale (20 if the player actually raced the finale, else 0),
 *     total: fPoints * 2 + bq + bd + bf
 *           where fPoints = sum of (65 - place) across every map the player raced.
 *   }
 *
 * Persisted on Phase.ranking as {ranking, maps} JSON; the round/phase view
 * reads it directly with no recomputation.
 */
class CalculateFinalRankingService
{
    private const PER_MAP_POINT_OFFSET = 65;
    private const BD_BONUS = 10;
    private const BF_BONUS = 20;

    public function __construct(
        private readonly MapRecordRepositoryInterface $mapRecordRepository,
        private readonly PhaseResultRepositoryInterface $phaseResultRepository,
        private readonly PhaseRepositoryInterface $phaseRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return array{ranking: list<array<string, mixed>>, maps: list<array{id: int, name: string, is_finale: bool}>}
     */
    public function calculate(Phase $finalPhase): array
    {
        if ($finalPhase->getType() !== PhaseType::Final) {
            throw new \RuntimeException("Cette action n'est disponible que pour la phase finale.");
        }

        $round = $finalPhase->getRound();

        if (!$round instanceof Round) {
            throw new \RuntimeException("La phase finale n'est pas associée à une manche.");
        }

        $laps = $finalPhase->getEffectiveLaps();

        $maps = [];
        $playerMapResults = []; // login => mapId => {place, temps}
        $playerPseudos = [];

        foreach ($round->getMaps() as $map) {
            if (!$map instanceof RoundMap || $map->getUid() === null || $map->getId() === null) {
                continue;
            }

            $maps[] = [
                'id' => $map->getId(),
                'name' => $map->getName() ?? '',
                'is_finale' => $map->isSurprise(),
            ];

            $records = $this->mapRecordRepository->findByMapUidWithPlayer(
                $map->getUid(),
                $round->getId(),
                $finalPhase->getId(),
            );

            $records = array_values(array_filter($records, static function (array $row) use ($laps): bool {
                $record = $row['record'];

                return $record->getGameMode() === GameMode::Laps && $record->getLaps() === $laps;
            }));

            if ($records === []) {
                continue;
            }

            usort($records, fn (array $a, array $b): int => $a['record']->getTime() <=> $b['record']->getTime());

            $place = 1;

            foreach ($records as $row) {
                $login = mb_strtolower($row['record']->getPlayerLogin());

                if (!isset($playerMapResults[$login])) {
                    $playerMapResults[$login] = [];
                }

                $playerMapResults[$login][$map->getId()] = [
                    'place' => $place,
                    'temps' => $row['record']->getTime(),
                ];

                if (!isset($playerPseudos[$login]) && $row['playerPseudo'] !== null) {
                    $playerPseudos[$login] = (string) $row['playerPseudo'];
                }

                ++$place;
            }
        }

        // bq: derived from the stored qualif ranking position.
        $qualifPhase = $round->getPhaseByType(PhaseType::Qualification);
        $qualifRanking = $qualifPhase?->getRanking() ?? [];
        $qualifPositions = [];

        foreach ($qualifRanking as $entry) {
            $login = mb_strtolower((string) ($entry['login'] ?? ''));

            if ($login !== '') {
                $qualifPositions[$login] = (int) ($entry['position'] ?? 0);

                if (!isset($playerPseudos[$login]) && isset($entry['pseudo'])) {
                    $playerPseudos[$login] = (string) $entry['pseudo'];
                }
            }
        }
        $totalQualPlayers = \count($qualifPositions);
        $nbMaps = \count($maps);

        // bd: who came from a semi (PhaseResult flagged is_qualified + qualified_to=final).
        $bdEligible = [];

        foreach ($round->getPhases() as $phase) {
            if ($phase->getType() !== PhaseType::SemiFinal) {
                continue;
            }

            foreach ($this->phaseResultRepository->findQualifiedByPhase($phase) as $result) {
                if ($result->getQualifiedTo() !== PhaseType::Final) {
                    continue;
                }
                $bdEligible[mb_strtolower($result->getPlayer()->getLogin())] = true;
            }
        }

        // bf: who actually raced the finale (= has at least one map result here, OR is on
        // the Final.players list set by FinalPopulationService).
        $bfEligible = $playerMapResults;

        foreach ($finalPhase->getPlayers() ?? [] as $login) {
            $bfEligible[mb_strtolower($login)] = true;
        }

        // Build entries for every player who has any presence (qualif rank or finale race).
        $allLogins = array_unique(array_merge(
            array_keys($qualifPositions),
            array_keys($playerMapResults),
            array_keys($bfEligible),
        ));

        $entries = [];

        foreach ($allLogins as $login) {
            $qualPos = $qualifPositions[$login] ?? ($totalQualPlayers + 1);
            $bq = max(0, $totalQualPlayers + $nbMaps - $qualPos);
            $bd = isset($bdEligible[$login]) ? self::BD_BONUS : 0;
            $bf = isset($bfEligible[$login]) ? self::BF_BONUS : 0;

            $mapsResults = $playerMapResults[$login] ?? [];
            $fPoints = 0;

            foreach ($mapsResults as $r) {
                $fPoints += self::PER_MAP_POINT_OFFSET - $r['place'];
            }

            $total = ($fPoints > 0) ? ($fPoints * 2 + $bq + $bd + $bf) : ($bq + $bd);

            $entries[] = [
                'login' => $login,
                'pseudo' => $playerPseudos[$login] ?? $login,
                'maps' => $mapsResults,
                'bq' => $bq,
                'bd' => $bd,
                'bf' => $bf,
                'total' => $total,
            ];
        }

        usort($entries, fn (array $a, array $b): int => $b['total'] <=> $a['total']);

        $position = 1;
        $previousTotal = null;
        $samePositionCount = 0;

        foreach ($entries as &$entry) {
            if ($previousTotal !== null && $entry['total'] !== $previousTotal) {
                $position += $samePositionCount;
                $samePositionCount = 1;
            } else {
                ++$samePositionCount;
            }
            $entry['position'] = $position;
            $previousTotal = $entry['total'];
        }
        unset($entry);

        $payload = [
            'ranking' => $entries,
            'maps' => $maps,
        ];

        $finalPhase->setRanking($payload);
        $finalPhase->setRankingUpdatedAt(new \DateTimeImmutable());
        $this->phaseRepository->save($finalPhase);

        $this->logger->info('[CalcFinal] computed', [
            'roundId' => $round->getId(),
            'finalPhaseId' => $finalPhase->getId(),
            'entries' => \count($entries),
            'maps' => $nbMaps,
            'totalQualPlayers' => $totalQualPlayers,
        ]);

        return $payload;
    }
}
