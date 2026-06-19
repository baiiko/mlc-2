<?php

declare(strict_types=1);

namespace App\Application\Championship\DTO;

use App\Domain\Championship\Entity\PhaseType;

/**
 * Inbound payload of POST /api/phase/result — a map result of a semi/final phase
 * emitted by the game server. See site-integration-plan.md §3.2.
 */
final readonly class PhaseResultNotificationDTO
{
    /**
     * @param array<array{login: string, time: int, position: int}> $results
     */
    public function __construct(
        public string $serverLogin,
        public PhaseType $phaseType,
        public ?int $groupNumber,
        public string $mapUid,
        public string $winnerLogin,
        public array $results,
        public ?\DateTimeImmutable $ts,
    ) {
    }
}
