<?php

declare(strict_types=1);

namespace App\Domain\Championship\Repository;

use App\Domain\Championship\Entity\Round;
use App\Domain\Championship\Entity\Season;

interface RoundRepositoryInterface
{
    public function save(Round $round): void;

    public function delete(Round $round): void;

    public function findById(int $id): ?Round;

    /**
     * @return Round[]
     */
    public function findBySeason(Season $season): array;

    /**
     * @return Round[]
     */
    public function findActiveRounds(): array;

    /**
     * @return Round[]
     */
    public function findWithOpenRegistration(): array;

    /**
     * Find the current active round, or the next upcoming one.
     */
    public function findCurrentOrUpcoming(): ?Round;
}
