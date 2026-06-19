<?php

declare(strict_types=1);

namespace App\Application\Championship\Service;

use App\Application\Championship\DTO\RecordNotificationDTO;

interface RecordIngestionServiceInterface
{
    /**
     * Persist (upsert) a record sent by the game server and trigger a ranking
     * recompute. Returns whether it was accepted and, if not, why.
     *
     * @return array{accepted: bool, reason: string|null}
     */
    public function ingest(RecordNotificationDTO $dto): array;
}
