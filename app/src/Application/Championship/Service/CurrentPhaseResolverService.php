<?php

declare(strict_types=1);

namespace App\Application\Championship\Service;

use App\Domain\Championship\Entity\PhaseType;
use App\Domain\Championship\Repository\PhaseRepositoryInterface;
use App\Domain\Championship\Repository\RoundRepositoryInterface;

/**
 * Resolves the chat-command name (qualification / demi / finale / training / free)
 * to send to the server plugin based on the current championship state.
 */
final readonly class CurrentPhaseResolverService
{
    public function __construct(
        private PhaseRepositoryInterface $phaseRepository,
        private RoundRepositoryInterface $roundRepository,
    ) {
    }

    public function resolveCurrentPhaseName(): string
    {
        $activePhase = $this->phaseRepository->findActivePlayablePhase();

        if ($activePhase !== null) {
            return match ($activePhase->getType()) {
                PhaseType::SemiFinal => 'demi',
                PhaseType::Final => 'finale',
                default => 'qualification',
            };
        }

        $round = $this->roundRepository->findCurrentOrUpcoming();

        if ($round === null) {
            return 'free';
        }

        $now = new \DateTimeImmutable();
        $starts = [];
        $ends = [];

        foreach ($round->getPhases() as $phase) {
            if ($phase->getStartAt() !== null) {
                $starts[] = $phase->getStartAt();
            }

            if ($phase->getEndAt() !== null) {
                $ends[] = $phase->getEndAt();
            }
        }

        if ($starts === [] || $ends === []) {
            return 'free';
        }

        return ($now >= min($starts) && $now <= max($ends)) ? 'training' : 'free';
    }
}
