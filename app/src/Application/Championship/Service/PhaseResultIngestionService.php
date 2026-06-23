<?php

declare(strict_types=1);

namespace App\Application\Championship\Service;

use App\Application\Championship\DTO\PhaseResultNotificationDTO;
use App\Domain\Championship\Entity\Phase;
use App\Domain\Championship\Entity\PhaseMapResult;
use App\Domain\Championship\Entity\PhaseResult;
use App\Domain\Championship\Entity\PhaseType;
use App\Domain\Championship\Entity\Round;
use App\Domain\Championship\Entity\RoundRegistration;
use App\Domain\Championship\Repository\PhaseMapResultRepositoryInterface;
use App\Domain\Championship\Repository\PhaseResultRepositoryInterface;
use App\Domain\Championship\Repository\RoundRegistrationRepositoryInterface;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\Repository\PlayerRepositoryInterface;

final readonly class PhaseResultIngestionService implements PhaseResultIngestionServiceInterface
{
    public function __construct(
        private CompetitionStateServiceInterface $competitionState,
        private PhaseMapResultRepositoryInterface $phaseMapResultRepository,
        private PhaseResultRepositoryInterface $phaseResultRepository,
        private PlayerRepositoryInterface $playerRepository,
        private RoundRegistrationRepositoryInterface $registrationRepository,
    ) {
    }

    public function ingest(PhaseResultNotificationDTO $dto): array
    {
        $phase = $this->competitionState->getActivePhase($dto->phaseType, $dto->groupNumber);

        if (!$phase instanceof Phase) {
            return $this->response(false, 'no_active_phase');
        }

        $this->upsertMapResult($phase, $dto);

        // Only a semi-final promotes its map winner to the final and reports completion.
        if ($dto->phaseType === PhaseType::SemiFinal) {
            $this->qualifyWinnerToFinal($phase, $dto->winnerLogin, $dto->results);

            [$qualifiedCount, $qualifyTarget] = $this->semiCompletion($phase);

            return $this->response(
                true,
                null,
                semiComplete: $qualifyTarget > 0 && $qualifiedCount >= $qualifyTarget,
                qualifiedCount: $qualifiedCount,
                qualifyTarget: $qualifyTarget,
            );
        }

        return $this->response(true, null);
    }

    /**
     * Number of players qualified in this specific semi vs the per-semi target
     * (qualify_from_semi_count split across the round's active semis, matching
     * MatchSettingsGeneratorService).
     *
     * @return array{0: int, 1: int} [qualifiedInThisPhase, perSemiTarget]
     */
    private function semiCompletion(Phase $phase): array
    {
        $qualified = \count($this->phaseResultRepository->findQualifiedByPhase($phase));

        $round = $phase->getRound();

        if (!$round instanceof Round) {
            return [$qualified, 0];
        }

        $activeSemiCount = 0;

        foreach ($round->getPhases() as $p) {
            if ($p->getType() === PhaseType::SemiFinal) {
                ++$activeSemiCount;
            }
        }

        $target = $activeSemiCount > 0
            ? (int) ceil($round->getQualifyFromSemiCount() / $activeSemiCount)
            : $round->getQualifyFromSemiCount();

        return [$qualified, $target];
    }

    /**
     * @return array{accepted: bool, reason: string|null, semiComplete: bool|null, qualifiedCount: int|null, qualifyTarget: int|null}
     */
    private function response(bool $accepted, ?string $reason, ?bool $semiComplete = null, ?int $qualifiedCount = null, ?int $qualifyTarget = null): array
    {
        return [
            'accepted' => $accepted,
            'reason' => $reason,
            'semiComplete' => $semiComplete,
            'qualifiedCount' => $qualifiedCount,
            'qualifyTarget' => $qualifyTarget,
        ];
    }

    private function upsertMapResult(Phase $phase, PhaseResultNotificationDTO $dto): void
    {
        $mapResult = $this->phaseMapResultRepository->findOneByUniqueKey($phase, $dto->mapUid, $dto->winnerLogin);

        if ($mapResult instanceof PhaseMapResult) {
            $mapResult->setResults($dto->results);
        } else {
            $mapResult = new PhaseMapResult($phase, $dto->mapUid, $dto->winnerLogin, $dto->results);
        }

        $this->phaseMapResultRepository->save($mapResult);
    }

    /**
     * @param array<array{login: string, time: int, position: int}> $results
     */
    private function qualifyWinnerToFinal(Phase $phase, string $winnerLogin, array $results): void
    {
        $round = $phase->getRound();

        if (!$round instanceof Round) {
            return;
        }

        $player = $this->playerRepository->findByLogin($winnerLogin);

        if (!$player instanceof Player) {
            return;
        }

        // Idempotent: a winner already recorded for this phase stays as-is.
        if ($this->phaseResultRepository->findByPhaseAndPlayer($phase, $player) instanceof PhaseResult) {
            return;
        }

        $registration = $this->registrationRepository->findByRoundAndPlayer($round, $player);

        if (!$registration instanceof RoundRegistration) {
            return;
        }

        $position = $this->countQualifiedToFinal($round) + 1;

        $result = new PhaseResult($phase, $player, $registration, $this->winnerTime($winnerLogin, $results), $position);
        $result->setIsQualified(true);
        $result->setQualifiedTo(PhaseType::Final);

        $this->phaseResultRepository->save($result);
    }

    private function countQualifiedToFinal(Round $round): int
    {
        $count = 0;

        foreach ($round->getPhases() as $phase) {
            if ($phase->getType() !== PhaseType::SemiFinal) {
                continue;
            }

            foreach ($this->phaseResultRepository->findQualifiedByPhase($phase) as $result) {
                if ($result->getQualifiedTo() === PhaseType::Final) {
                    ++$count;
                }
            }
        }

        return $count;
    }

    /**
     * @param array<array{login: string, time: int, position: int}> $results
     */
    private function winnerTime(string $winnerLogin, array $results): int
    {
        foreach ($results as $entry) {
            if (($entry['login'] ?? null) === $winnerLogin) {
                return (int) ($entry['time'] ?? 0);
            }
        }

        return 0;
    }
}
