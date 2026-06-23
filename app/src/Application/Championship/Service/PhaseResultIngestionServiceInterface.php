<?php

declare(strict_types=1);

namespace App\Application\Championship\Service;

use App\Application\Championship\DTO\PhaseResultNotificationDTO;

interface PhaseResultIngestionServiceInterface
{
    /**
     * Persist (upsert) the map result of a semi/final phase, and — for a semi —
     * record the winner's qualification to the final.
     *
     * The response carries the semi-completion signal the game server needs to
     * switch to training once enough players have qualified (the site owns
     * phase_result + qualify_from_semi_count). For a final, the completion
     * fields are null.
     *
     * @return array{accepted: bool, reason: string|null, semiComplete: bool|null, qualifiedCount: int|null, qualifyTarget: int|null}
     */
    public function ingest(PhaseResultNotificationDTO $dto): array;
}
