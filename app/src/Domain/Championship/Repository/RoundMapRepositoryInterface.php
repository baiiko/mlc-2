<?php

declare(strict_types=1);

namespace App\Domain\Championship\Repository;

use App\Domain\Championship\Entity\RoundMap;

interface RoundMapRepositoryInterface
{
    /**
     * @return RoundMap[]
     */
    public function findPaginated(
        int $page,
        int $perPage = 30,
        ?string $search = null,
        ?int $roundId = null,
        ?int $seasonId = null,
        ?string $author = null,
    ): array;

    public function countAll(
        ?string $search = null,
        ?int $roundId = null,
        ?int $seasonId = null,
        ?string $author = null,
    ): int;
}
