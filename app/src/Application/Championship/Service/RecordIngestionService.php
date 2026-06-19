<?php

declare(strict_types=1);

namespace App\Application\Championship\Service;

use App\Application\Championship\DTO\RecordNotificationDTO;
use App\Application\Championship\Message\UpdateRankingMessage;
use App\Domain\Championship\Entity\MapRecord;
use App\Domain\Championship\Entity\Phase;
use App\Domain\Championship\Enum\GameMode;
use App\Domain\Championship\Repository\MapRecordRepositoryInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class RecordIngestionService implements RecordIngestionServiceInterface
{
    public function __construct(
        private CompetitionStateServiceInterface $competitionState,
        private MapRecordRepositoryInterface $mapRecordRepository,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function ingest(RecordNotificationDTO $dto): array
    {
        if (!$dto->isCompetitor) {
            return ['accepted' => false, 'reason' => 'not_competitor'];
        }

        $phase = $this->competitionState->getActivePhase($dto->phaseType, $dto->groupNumber);

        if (!$phase instanceof Phase) {
            return ['accepted' => false, 'reason' => 'no_active_phase'];
        }

        $roundId = $phase->getRound()?->getId();
        $gameMode = GameMode::tryFrom($dto->gameMode) ?? GameMode::Laps;

        $record = $this->mapRecordRepository->findOneByUniqueKey(
            $dto->mapUid,
            $dto->playerLogin,
            $dto->laps,
            $gameMode,
            $roundId,
            $phase->getId(),
        ) ?? (new MapRecord($dto->mapUid, $dto->playerLogin, $dto->laps, $dto->time, $gameMode))
            ->setRoundId($roundId)
            ->setPhase($phase);

        $record->setTime($dto->time);
        $record->setCheckpoints($dto->checkpoints);

        if ($dto->playerNickname !== null) {
            $record->setPlayer($dto->playerNickname);
        }

        $this->mapRecordRepository->save($record);

        $this->messageBus->dispatch(new UpdateRankingMessage(
            recordedAt: $dto->ts ?? new \DateTimeImmutable(),
        ));

        return ['accepted' => true, 'reason' => null];
    }
}
