<?php

declare(strict_types=1);

namespace App\Domain\Team\Repository;

use App\Domain\Team\Entity\Team;

interface TeamRepositoryInterface
{
    public function save(Team $team): void;

    public function delete(Team $team): void;

    public function findById(int $id): ?Team;

    /**
     * Find a team by ID even if it has been soft-deleted.
     * Useful when historical rankings still reference disbanded teams.
     */
    public function findByIdIncludingDeleted(int $id): ?Team;

    public function findByTag(string $tag): ?Team;

    /**
     * @return Team[]
     */
    public function findAll(): array;
}
