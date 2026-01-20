<?php

declare(strict_types=1);

namespace App\Domain\Championship\Repository;

use App\Domain\Championship\Entity\Season;

interface SeasonRepositoryInterface
{
    public function save(Season $season): void;

    public function delete(Season $season): void;

    public function findById(int $id): ?Season;

    public function findBySlug(string $slug): ?Season;

    public function findActive(): ?Season;

    /**
     * @return Season[]
     */
    public function findAll(): array;

    /**
     * @return Season[]
     */
    public function findAllActive(): array;
}
