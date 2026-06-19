<?php

declare(strict_types=1);

namespace App\Application\Championship\DTO;

use App\Domain\Championship\Entity\PhaseType;

/**
 * Inbound payload of POST /api/record — a new player record emitted by the game
 * server during a competition phase. See site-integration-plan.md §3.1.
 */
final readonly class RecordNotificationDTO
{
    /**
     * @param array<int, int>|null $checkpoints
     */
    public function __construct(
        public string $serverLogin,
        public string $playerLogin,
        public ?string $playerNickname,
        public string $mapUid,
        public int $gameMode,
        public int $laps,
        public int $time,
        public PhaseType $phaseType,
        public ?int $groupNumber,
        public bool $isCompetitor,
        public ?array $checkpoints,
        public ?\DateTimeImmutable $ts,
    ) {
    }
}
