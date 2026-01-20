<?php

declare(strict_types=1);

namespace App\Domain\Championship\Repository;

use App\Domain\Championship\Entity\Phase;
use App\Domain\Championship\Entity\PhaseType;
use App\Domain\Championship\Entity\Round;

interface PhaseRepositoryInterface
{
    public function save(Phase $phase): void;

    public function delete(Phase $phase): void;

    public function findById(int $id): ?Phase;

    /**
     * @return Phase[]
     */
    public function findByRound(Round $round): array;

    public function findByRoundAndType(Round $round, PhaseType $type): ?Phase;
}
