<?php

declare(strict_types=1);

namespace App\Tests\Application\Championship\Service;

use App\Application\Championship\Service\CompetitionStateService;
use App\Domain\Championship\Entity\Phase;
use App\Domain\Championship\Entity\PhaseType;
use App\Domain\Championship\Entity\Round;
use App\Domain\Championship\Entity\RoundRegistration;
use App\Domain\Championship\Repository\PhaseRepositoryInterface;
use App\Domain\Championship\Repository\RoundRegistrationRepositoryInterface;
use App\Domain\Championship\Repository\RoundRepositoryInterface;
use App\Domain\Championship\Repository\ServerRepositoryInterface;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\Repository\PlayerRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class CompetitionStateServiceTest extends TestCase
{
    private RoundRepositoryInterface $rounds;
    private PhaseRepositoryInterface $phases;
    private RoundRegistrationRepositoryInterface $registrations;
    private PlayerRepositoryInterface $players;
    private ServerRepositoryInterface $servers;
    private CompetitionStateService $service;

    protected function setUp(): void
    {
        $this->rounds = $this->createStub(RoundRepositoryInterface::class);
        $this->phases = $this->createStub(PhaseRepositoryInterface::class);
        $this->registrations = $this->createStub(RoundRegistrationRepositoryInterface::class);
        $this->players = $this->createStub(PlayerRepositoryInterface::class);
        $this->servers = $this->createStub(ServerRepositoryInterface::class);

        $this->service = new CompetitionStateService(
            $this->rounds,
            $this->phases,
            $this->registrations,
            $this->players,
            $this->servers,
        );
    }

    private function roundWithPhase(PhaseType $type, ?int $group, \DateTimeImmutable $startAt, ?\DateTimeImmutable $endAt): Round
    {
        $round = new Round();
        $phase = new Phase($round, $type, $startAt);
        $phase->setEndAt($endAt);
        $phase->setGroupNumber($group);
        $round->addPhase($phase);

        return $round;
    }

    public function testCanActivateIsTrueWhenWindowIsOpen(): void
    {
        $now = new \DateTimeImmutable();
        $round = $this->roundWithPhase(PhaseType::SemiFinal, 1, $now->modify('-1 hour'), $now->modify('+1 hour'));

        $this->phases->method('findActivePlayablePhase')->willReturn(null);
        $this->rounds->method('findCurrentOrUpcoming')->willReturn($round);

        self::assertTrue($this->service->canActivate(PhaseType::SemiFinal, 1));
        self::assertFalse($this->service->isEnded(PhaseType::SemiFinal, 1));
    }

    public function testCanActivateIsFalseForFuturePhase(): void
    {
        $now = new \DateTimeImmutable();
        $round = $this->roundWithPhase(PhaseType::Final, null, $now->modify('+2 hours'), $now->modify('+3 hours'));

        $this->phases->method('findActivePlayablePhase')->willReturn(null);
        $this->rounds->method('findCurrentOrUpcoming')->willReturn($round);

        self::assertFalse($this->service->canActivate(PhaseType::Final));
        self::assertFalse($this->service->isEnded(PhaseType::Final));
    }

    public function testIsEndedIsTrueWhenEndAtIsPast(): void
    {
        $now = new \DateTimeImmutable();
        $round = $this->roundWithPhase(PhaseType::Qualification, null, $now->modify('-3 hours'), $now->modify('-1 hour'));

        $this->phases->method('findActivePlayablePhase')->willReturn(null);
        $this->rounds->method('findCurrentOrUpcoming')->willReturn($round);

        self::assertTrue($this->service->isEnded(PhaseType::Qualification));
        self::assertFalse($this->service->canActivate(PhaseType::Qualification));
    }

    public function testGroupFilterDisambiguatesPhases(): void
    {
        $now = new \DateTimeImmutable();
        $round = new Round();
        $semi1 = (new Phase($round, PhaseType::SemiFinal, $now->modify('-1 hour')))
            ->setEndAt($now->modify('+1 hour'))
            ->setGroupNumber(1);
        $semi2 = (new Phase($round, PhaseType::SemiFinal, $now->modify('+2 hours')))
            ->setEndAt($now->modify('+3 hours'))
            ->setGroupNumber(2);
        $round->addPhase($semi1);
        $round->addPhase($semi2);

        $this->phases->method('findActivePlayablePhase')->willReturn(null);
        $this->rounds->method('findCurrentOrUpcoming')->willReturn($round);

        self::assertTrue($this->service->canActivate(PhaseType::SemiFinal, 1));
        self::assertFalse($this->service->canActivate(PhaseType::SemiFinal, 2));
    }

    public function testIsCompetitorTrueWhenRegistered(): void
    {
        $round = new Round();
        $player = new Player('alice', 'alice@example.com', 'Alice');

        $this->phases->method('findActivePlayablePhase')->willReturn(null);
        $this->rounds->method('findCurrentOrUpcoming')->willReturn($round);
        $this->players->method('findByLogin')->with('alice')->willReturn($player);
        $this->registrations->method('findByRoundAndPlayer')
            ->with($round, $player)
            ->willReturn($this->createStub(RoundRegistration::class));

        self::assertTrue($this->service->isCompetitor('alice'));
    }

    public function testIsCompetitorFalseWhenPlayerUnknown(): void
    {
        $round = new Round();

        $this->phases->method('findActivePlayablePhase')->willReturn(null);
        $this->rounds->method('findCurrentOrUpcoming')->willReturn($round);
        $this->players->method('findByLogin')->willReturn(null);

        self::assertFalse($this->service->isCompetitor('ghost'));
    }

    public function testPhaseToArrayShape(): void
    {
        $now = new \DateTimeImmutable('2026-06-19T12:00:00+00:00');
        $phase = (new Phase(new Round(), PhaseType::Qualification, $now))
            ->setEndAt($now->modify('+2 hours'))
            ->setGroupNumber(null)
            ->setPlayers(['alice', 'bob']);

        $array = $this->service->phaseToArray($phase);

        self::assertSame('qualification', $array['type']);
        self::assertSame(['alice', 'bob'], $array['players']);
        self::assertSame(5, $array['laps']); // effective laps for qualification
        self::assertSame('2026-06-19T12:00:00+00:00', $array['startAt']);
    }

    public function testRoundToArrayCountsQualificationMapsExcludingSurprise(): void
    {
        $round = new Round();
        $round->addMap(new \App\Domain\Championship\Entity\RoundMap($round, 'Map A', 'uidA'));
        $round->addMap(new \App\Domain\Championship\Entity\RoundMap($round, 'Map B', 'uidB'));
        $round->addMap((new \App\Domain\Championship\Entity\RoundMap($round, 'Surprise', 'uidS'))->setIsSurprise(true));

        $array = $this->service->roundToArray($round);

        self::assertSame(2, $array['mapCount']);
    }

    public function testGetActivePhaseWithoutTypeDelegatesToPlayablePhase(): void
    {
        $phase = new Phase(new Round(), PhaseType::Final, new \DateTimeImmutable());
        $this->phases->method('findActivePlayablePhase')->willReturn($phase);

        self::assertSame($phase, $this->service->getActivePhase(null));
    }
}
