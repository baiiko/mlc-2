<?php

declare(strict_types=1);

namespace App\Application\Championship\MessageHandler;

use App\Application\Championship\Message\UpdateRankingMessage;
use App\Application\Championship\Service\RoundRankingServiceInterface;
use App\Domain\Championship\Repository\PhaseRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class UpdateRankingMessageHandler
{
    public function __construct(
        private PhaseRepositoryInterface $phaseRepository,
        private RoundRankingServiceInterface $roundRankingService,
    ) {
    }

    public function __invoke(UpdateRankingMessage $message): void
    {
        $phase = $this->phaseRepository->findActiveQualificationPhase();

        if ($phase === null) {
            return;
        }

        // Skip if ranking is already up-to-date (updated after this record was made)
        $rankingUpdatedAt = $phase->getRankingUpdatedAt();
        if ($rankingUpdatedAt !== null && $rankingUpdatedAt >= $message->recordedAt) {
            return;
        }

        $round = $phase->getRound();
        if ($round === null) {
            return;
        }

        // Calculate and save ranking
        $ranking = $this->roundRankingService->calculateQualificationRanking($round, $phase);
        $phase->setRanking($ranking);
        $phase->setRankingUpdatedAt(new \DateTimeImmutable());
        $this->phaseRepository->save($phase);
    }
}
