<?php

declare(strict_types=1);

namespace App\Domain\Championship\Repository;

use App\Domain\Championship\Entity\Phase;
use App\Domain\Championship\Entity\PhaseMapResult;

interface PhaseMapResultRepositoryInterface
{
    public function save(PhaseMapResult $result): void;

    /**
     * Find the map result matching the natural unique key (map_uid, winner, phase)
     * for idempotent upserts.
     */
    public function findOneByUniqueKey(Phase $phase, string $mapUid, string $winner): ?PhaseMapResult;
}
