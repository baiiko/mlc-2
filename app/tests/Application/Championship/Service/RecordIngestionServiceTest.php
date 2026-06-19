<?php

declare(strict_types=1);

namespace App\Tests\Application\Championship\Service;

use App\Application\Championship\DTO\RecordNotificationDTO;
use App\Application\Championship\Service\CompetitionStateServiceInterface;
use App\Application\Championship\Service\RecordIngestionService;
use App\Domain\Championship\Entity\Phase;
use App\Domain\Championship\Entity\PhaseType;
use App\Domain\Championship\Entity\Round;
use App\Domain\Championship\Enum\GameMode;
use App\Domain\Championship\Repository\MapRecordRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class RecordIngestionServiceTest extends TestCase
{
    private function dto(bool $isCompetitor = true): RecordNotificationDTO
    {
        return new RecordNotificationDTO(
            serverLogin: 'srv-qualif-1',
            playerLogin: 'alice',
            playerNickname: '$fffAlice',
            mapUid: 'abc',
            gameMode: GameMode::Laps->value,
            laps: 5,
            time: 54213,
            phaseType: PhaseType::Qualification,
            groupNumber: null,
            isCompetitor: $isCompetitor,
            checkpoints: [12000, 33000, 54213],
            ts: null,
        );
    }

    public function testSkipsNonCompetitor(): void
    {
        $service = new RecordIngestionService(
            $this->createStub(CompetitionStateServiceInterface::class),
            $this->createStub(MapRecordRepositoryInterface::class),
            $this->createStub(MessageBusInterface::class),
        );

        self::assertSame(
            ['accepted' => false, 'reason' => 'not_competitor'],
            $service->ingest($this->dto(isCompetitor: false)),
        );
    }

    public function testSkipsWhenNoActivePhase(): void
    {
        $state = $this->createStub(CompetitionStateServiceInterface::class);
        $state->method('getActivePhase')->willReturn(null);

        $service = new RecordIngestionService(
            $state,
            $this->createStub(MapRecordRepositoryInterface::class),
            $this->createStub(MessageBusInterface::class),
        );

        self::assertSame(
            ['accepted' => false, 'reason' => 'no_active_phase'],
            $service->ingest($this->dto()),
        );
    }

    public function testPersistsAndDispatchesRankingRecompute(): void
    {
        $phase = new Phase(new Round(), PhaseType::Qualification, new \DateTimeImmutable());

        $state = $this->createStub(CompetitionStateServiceInterface::class);
        $state->method('getActivePhase')->willReturn($phase);

        $records = $this->createMock(MapRecordRepositoryInterface::class);
        $records->method('findOneByUniqueKey')->willReturn(null);
        $records->expects(self::once())->method('save');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())->method('dispatch')->willReturn(new Envelope(new \stdClass()));

        $service = new RecordIngestionService($state, $records, $bus);

        self::assertSame(['accepted' => true, 'reason' => null], $service->ingest($this->dto()));
    }
}
