<?php

declare(strict_types=1);

namespace App\Tests\Application\Championship\Service;

use App\Application\Championship\DTO\PhaseResultNotificationDTO;
use App\Application\Championship\Service\CompetitionStateServiceInterface;
use App\Application\Championship\Service\PhaseResultIngestionService;
use App\Domain\Championship\Entity\Phase;
use App\Domain\Championship\Entity\PhaseResult;
use App\Domain\Championship\Entity\PhaseType;
use App\Domain\Championship\Entity\Round;
use App\Domain\Championship\Entity\RoundRegistration;
use App\Domain\Championship\Repository\PhaseMapResultRepositoryInterface;
use App\Domain\Championship\Repository\PhaseResultRepositoryInterface;
use App\Domain\Championship\Repository\RoundRegistrationRepositoryInterface;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\Repository\PlayerRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class PhaseResultIngestionServiceTest extends TestCase
{
    private CompetitionStateServiceInterface $state;
    private PhaseMapResultRepositoryInterface $mapResults;
    private PlayerRepositoryInterface $players;
    private RoundRegistrationRepositoryInterface $registrations;

    protected function setUp(): void
    {
        $this->state = $this->createStub(CompetitionStateServiceInterface::class);
        $this->mapResults = $this->createStub(PhaseMapResultRepositoryInterface::class);
        $this->mapResults->method('findOneByUniqueKey')->willReturn(null);
        $this->players = $this->createStub(PlayerRepositoryInterface::class);
        $this->registrations = $this->createStub(RoundRegistrationRepositoryInterface::class);
    }

    private function dto(PhaseType $type): PhaseResultNotificationDTO
    {
        return new PhaseResultNotificationDTO(
            serverLogin: 'srv-demi-1',
            phaseType: $type,
            groupNumber: 1,
            mapUid: 'abc',
            winnerLogin: 'alice',
            results: [
                ['login' => 'alice', 'time' => 54213, 'position' => 1],
                ['login' => 'bob', 'time' => 55001, 'position' => 2],
            ],
            ts: null,
        );
    }

    private function semiPhase(): Phase
    {
        $round = new Round();
        $phase = (new Phase($round, PhaseType::SemiFinal, new \DateTimeImmutable()))->setGroupNumber(1);
        $round->addPhase($phase);

        return $phase;
    }

    public function testSkipsWhenNoActivePhase(): void
    {
        $this->state->method('getActivePhase')->willReturn(null);
        $phaseResults = $this->createStub(PhaseResultRepositoryInterface::class);

        $service = new PhaseResultIngestionService($this->state, $this->mapResults, $phaseResults, $this->players, $this->registrations);

        self::assertSame(
            ['accepted' => false, 'reason' => 'no_active_phase', 'semiComplete' => null, 'qualifiedCount' => null, 'qualifyTarget' => null],
            $service->ingest($this->dto(PhaseType::SemiFinal)),
        );
    }

    public function testSemiFinalQualifiesWinnerToFinalAtNextPosition(): void
    {
        $phase = $this->semiPhase();
        $phase->getRound()->setQualifyFromSemiCount(8);
        $this->state->method('getActivePhase')->willReturn($phase);
        $this->players->method('findByLogin')->with('alice')->willReturn(new Player('alice', 'a@example.com', 'Alice'));
        $this->registrations->method('findByRoundAndPlayer')->willReturn($this->createStub(RoundRegistration::class));

        $phaseResults = $this->createMock(PhaseResultRepositoryInterface::class);
        $phaseResults->method('findByPhaseAndPlayer')->willReturn(null);
        $phaseResults->method('findQualifiedByPhase')->willReturn([]); // no one qualified yet
        $phaseResults->expects(self::once())->method('save')->with(self::callback(
            static fn (PhaseResult $r): bool => $r->isQualified()
                && $r->getQualifiedTo() === PhaseType::Final
                && $r->getPosition() === 1
                && $r->getTime() === 54213,
        ));

        $service = new PhaseResultIngestionService($this->state, $this->mapResults, $phaseResults, $this->players, $this->registrations);

        self::assertSame(
            ['accepted' => true, 'reason' => null, 'semiComplete' => false, 'qualifiedCount' => 0, 'qualifyTarget' => 8],
            $service->ingest($this->dto(PhaseType::SemiFinal)),
        );
    }

    public function testSemiCompleteSignalWhenTargetReached(): void
    {
        $phase = $this->semiPhase();
        $phase->getRound()->setQualifyFromSemiCount(1); // single semi → per-semi target = 1
        $this->state->method('getActivePhase')->willReturn($phase);
        $this->players->method('findByLogin')->willReturn(new Player('alice', 'a@example.com', 'Alice'));

        $phaseResults = $this->createMock(PhaseResultRepositoryInterface::class);
        // Winner already recorded (idempotent path) → no new save, but the semi is now complete.
        $phaseResults->method('findByPhaseAndPlayer')->willReturn($this->createStub(PhaseResult::class));
        $phaseResults->method('findQualifiedByPhase')->willReturn([$this->createStub(PhaseResult::class)]);
        $phaseResults->expects(self::never())->method('save');

        $service = new PhaseResultIngestionService($this->state, $this->mapResults, $phaseResults, $this->players, $this->registrations);

        self::assertSame(
            ['accepted' => true, 'reason' => null, 'semiComplete' => true, 'qualifiedCount' => 1, 'qualifyTarget' => 1],
            $service->ingest($this->dto(PhaseType::SemiFinal)),
        );
    }

    public function testFinalDoesNotCreatePhaseResult(): void
    {
        $round = new Round();
        $final = (new Phase($round, PhaseType::Final, new \DateTimeImmutable()))->setGroupNumber(1);
        $round->addPhase($final);
        $this->state->method('getActivePhase')->willReturn($final);

        $phaseResults = $this->createMock(PhaseResultRepositoryInterface::class);
        $phaseResults->expects(self::never())->method('save');

        $service = new PhaseResultIngestionService($this->state, $this->mapResults, $phaseResults, $this->players, $this->registrations);

        self::assertSame(
            ['accepted' => true, 'reason' => null, 'semiComplete' => null, 'qualifiedCount' => null, 'qualifyTarget' => null],
            $service->ingest($this->dto(PhaseType::Final)),
        );
    }
}
