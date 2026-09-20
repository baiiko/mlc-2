<?php

declare(strict_types=1);

namespace App\Tests\Application\Championship\Service;

use App\Application\Championship\Service\MatchSettingsGeneratorService;
use App\Domain\Championship\Entity\Phase;
use App\Domain\Championship\Entity\PhaseType;
use App\Domain\Championship\Entity\Round;
use PHPUnit\Framework\TestCase;

final class MatchSettingsGeneratorServiceTest extends TestCase
{
    public function testSemiFinalFilenameCarriesGroupNumber(): void
    {
        $phase = (new Phase(new Round(), PhaseType::SemiFinal, new \DateTimeImmutable()))->setGroupNumber(2);

        self::assertSame('demi2.xml', MatchSettingsGeneratorService::getFilenameForPhase($phase));
    }

    public function testFinalFilenameIsSharedAcrossServers(): void
    {
        $phase = (new Phase(new Round(), PhaseType::Final, new \DateTimeImmutable()))->setGroupNumber(2);

        self::assertSame('finale.xml', MatchSettingsGeneratorService::getFilenameForPhase($phase));
    }
}
