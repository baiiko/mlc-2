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
        [$round, $qualif] = $this->roundWithQualification(PhaseType::Final);

        $this->finalPopulation
            ->expects(self::once())
            ->method('populateFinal')
            ->with($round->getPhaseByType(PhaseType::Final))
            ->willReturn(['direct' => ['alice', 'bob'], 'fromSemis' => [], 'all' => ['alice', 'bob']]);

        $result = $this->service->closeQualification($qualif);

        self::assertSame([], $result['semis']);
        self::assertSame(['alice', 'bob'], $result['final']);
    }

    public function testRoundWithoutSemisNorFinalCannotBeClosed(): void
    {
        [, $qualif] = $this->roundWithQualification();

        $this->finalPopulation->expects(self::never())->method('populateFinal');

        $this->expectException(\RuntimeException::class);

        $this->service->closeQualification($qualif);
    }

    public function testRoundWithSemisLeavesTheFinalUntouched(): void
    {
        [$round, $qualif] = $this->roundWithQualification(PhaseType::SemiFinal, PhaseType::Final);
        $round->setQualifyToSemiCount(8);
        $round->getPhaseByType(PhaseType::SemiFinal)?->setGroupNumber(1);

        $this->finalPopulation->expects(self::never())->method('populateFinal');

        $result = $this->service->closeQualification($qualif);

        self::assertSame([1 => []], $result['semis']);
        self::assertNull($result['final']);
    }

    /**
     * @return array{Round, Phase} the round and its qualification phase (with an empty ranking)
     */
    private function roundWithQualification(PhaseType ...$otherPhases): array
    {
        $round = new Round();
        $qualif = (new Phase($round, PhaseType::Qualification, new \DateTimeImmutable()))->setRanking([]);
        $round->addPhase($qualif);

        foreach ($otherPhases as $type) {
            $round->addPhase(new Phase($round, $type, new \DateTimeImmutable()));
        }

        return [$round, $qualif];
    }
}
