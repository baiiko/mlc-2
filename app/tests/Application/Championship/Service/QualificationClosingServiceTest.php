<?php

declare(strict_types=1);

namespace App\Tests\Application\Championship\Service;

use App\Application\Championship\Service\FinalPopulationService;
use App\Application\Championship\Service\MatchSettingsGeneratorService;
use App\Application\Championship\Service\QualificationClosingService;
use App\Domain\Championship\Entity\Phase;
use App\Domain\Championship\Entity\PhaseType;
use App\Domain\Championship\Entity\Round;
use App\Domain\Championship\Repository\PhaseRepositoryInterface;
use App\Domain\Championship\Repository\RoundRegistrationRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class QualificationClosingServiceTest extends TestCase
{
    private FinalPopulationService&MockObject $finalPopulation;

    private QualificationClosingService $service;

    protected function setUp(): void
    {
        $registrations = self::createStub(RoundRegistrationRepositoryInterface::class);
        $registrations->method('findByRound')->willReturn([]);

        $this->finalPopulation = $this->createMock(FinalPopulationService::class);

        $this->service = new QualificationClosingService(
            $registrations,
            self::createStub(PhaseRepositoryInterface::class),
            self::createStub(MatchSettingsGeneratorService::class),
            $this->finalPopulation,
            new NullLogger(),
        );
    }

    public function testRoundWithoutSemisPopulatesTheFinalDirectly(): void
    {
        $round = new Round();
        $qualif = (new Phase($round, PhaseType::Qualification, new \DateTimeImmutable()))->setRanking([]);
        $final = new Phase($round, PhaseType::Final, new \DateTimeImmutable());
        $round->addPhase($qualif)->addPhase($final);

        $this->finalPopulation
            ->expects(self::once())
            ->method('populateFinal')
            ->with($final)
            ->willReturn(['direct' => ['alice', 'bob'], 'fromSemis' => [], 'all' => ['alice', 'bob']]);

        $result = $this->service->closeQualification($qualif);

        self::assertSame([], $result['semis']);
        self::assertSame(['alice', 'bob'], $result['final']);
    }

    public function testRoundWithoutSemisNorFinalCannotBeClosed(): void
    {
        $round = new Round();
        $qualif = (new Phase($round, PhaseType::Qualification, new \DateTimeImmutable()))->setRanking([]);
        $round->addPhase($qualif);

        $this->finalPopulation->expects(self::never())->method('populateFinal');

        $this->expectException(\RuntimeException::class);

        $this->service->closeQualification($qualif);
    }

    public function testRoundWithSemisLeavesTheFinalUntouched(): void
    {
        $round = new Round();
        $round->setQualifyToSemiCount(8);
        $qualif = (new Phase($round, PhaseType::Qualification, new \DateTimeImmutable()))->setRanking([]);
        $semi = (new Phase($round, PhaseType::SemiFinal, new \DateTimeImmutable()))->setGroupNumber(1);
        $final = new Phase($round, PhaseType::Final, new \DateTimeImmutable());
        $round->addPhase($qualif)->addPhase($semi)->addPhase($final);

        $this->finalPopulation->expects(self::never())->method('populateFinal');

        $result = $this->service->closeQualification($qualif);

        self::assertSame([1 => []], $result['semis']);
        self::assertNull($result['final']);
    }
}
