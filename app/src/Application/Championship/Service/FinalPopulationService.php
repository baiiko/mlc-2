<?php

declare(strict_types=1);

namespace App\Application\Championship\Service;

use App\Domain\Championship\Entity\Phase;
use App\Domain\Championship\Entity\PhaseType;
use App\Domain\Championship\Entity\Round;
use App\Domain\Championship\Entity\RoundMap;
use App\Domain\Championship\Entity\RoundRegistration;
use App\Domain\Championship\Repository\PhaseRepositoryInterface;
use App\Domain\Championship\Repository\PhaseResultRepositoryInterface;
use App\Domain\Championship\Repository\RoundRegistrationRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Populates the player list of a Final phase from:
 *   - the direct qualifiers from the qualification ranking (same rule as
 *     phase.html.twig / QualificationClosingService)
 *   - the players flagged qualified-to-final in each semi-final's PhaseResult
 */
class FinalPopulationService
{
    public function __construct(
        private readonly RoundRegistrationRepositoryInterface $registrationRepository,
        private readonly PhaseResultRepositoryInterface $phaseResultRepository,
        private readonly PhaseRepositoryInterface $phaseRepository,
        private readonly MatchSettingsGeneratorService $matchSettingsGenerator,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return array{direct: list<string>, fromSemis: list<string>, all: list<string>}
     */
    public function populateFinal(Phase $finalPhase): array
    {
        if ($finalPhase->getType() !== PhaseType::Final) {
            throw new \RuntimeException("Cette action n'est disponible que pour la phase finale.");
        }

        $round = $finalPhase->getRound();

        if (!$round instanceof Round) {
            throw new \RuntimeException("La phase finale n'est pas associée à une manche.");
        }

        $direct = $this->collectDirectQualifiers($round);
        $fromSemis = $this->collectSemiQualifiers($round);

        // Preserve order: direct first (top of qualif), then semi qualifiers.
        $all = array_values(array_unique(array_merge($direct, $fromSemis)));

        $this->logger->info('[PopulateFinal] selection', [
            'roundId' => $round->getId(),
            'finalPhaseId' => $finalPhase->getId(),
            'directCount' => \count($direct),
            'fromSemisCount' => \count($fromSemis),
            'totalCount' => \count($all),
        ]);

        $finalPhase->setPlayers($all);
        $this->phaseRepository->save($finalPhase);

        try {
            $this->matchSettingsGenerator->saveForPhase($finalPhase);
        } catch (\Throwable $e) {
            $this->logger->warning('[PopulateFinal] match settings generation failed', [
                'finalPhaseId' => $finalPhase->getId(),
                'message' => $e->getMessage(),
            ]);
        }

        return [
            'direct' => $direct,
            'fromSemis' => $fromSemis,
            'all' => $all,
        ];
    }

    /**
     * Walks the qualification ranking the same way phase.html.twig and
     * QualificationClosingService do, and returns the logins of the top
     * qualifyToFinalCount players who skip semis and go straight to finale.
     *
     * @return list<string>
     */
    private function collectDirectQualifiers(Round $round): array
    {
        $qualifPhase = $round->getPhaseByType(PhaseType::Qualification);

        if (!$qualifPhase instanceof Phase) {
            return [];
        }

        $ranking = $qualifPhase->getRanking();

        if (!\is_array($ranking) || $ranking === []) {
            return [];
        }

        $qualifyToFinalCount = $round->getQualifyToFinalCount();

        if ($qualifyToFinalCount <= 0) {
            return [];
        }

        usort($ranking, static fn (array $a, array $b): int => ($a['position'] ?? 0) <=> ($b['position'] ?? 0));

        $registrationsByLogin = [];

        foreach ($this->registrationRepository->findByRound($round) as $registration) {
            $registrationsByLogin[mb_strtolower($registration->getPlayer()->getLogin())] = $registration;
        }

        $totalMaps = 0;

        foreach ($round->getMaps() as $map) {
            if (!$map instanceof RoundMap || $map->isSurprise()) {
                continue;
            }
            ++$totalMaps;
        }

        $direct = [];

        foreach ($ranking as $entry) {
            if (\count($direct) >= $qualifyToFinalCount) {
                break;
            }

            $login = mb_strtolower((string) ($entry['login'] ?? ''));
            $registration = $registrationsByLogin[$login] ?? null;

            if (!$registration instanceof RoundRegistration) {
                continue;
            }

            if (($entry['nbMaps'] ?? 0) < $totalMaps) {
                continue;
            }

            if (!$registration->isAvailableFinal()) {
                continue;
            }

            $direct[] = $login;
        }

        return $direct;
    }

    /**
     * Collects every login flagged qualified-to-final across every active
     * semi-final phase of the round, ordered by phase id then result position.
     *
     * @return list<string>
     */
    private function collectSemiQualifiers(Round $round): array
    {
        $logins = [];

        foreach ($round->getPhases() as $phase) {
            if ($phase->getType() !== PhaseType::SemiFinal) {
                continue;
            }

            foreach ($this->phaseResultRepository->findQualifiedByPhase($phase) as $result) {
                if ($result->getQualifiedTo() !== PhaseType::Final) {
                    continue;
                }
                $logins[] = mb_strtolower($result->getPlayer()->getLogin());
            }
        }

        return $logins;
    }
}
