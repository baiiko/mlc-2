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
     * @return array{accepted: bool, reason: string|null}
     */
    public function ingest(PhaseResultNotificationDTO $dto): array;
}
