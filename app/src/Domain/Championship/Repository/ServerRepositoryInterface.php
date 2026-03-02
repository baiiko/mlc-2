<?php

declare(strict_types=1);

namespace App\Domain\Championship\Repository;

use App\Domain\Championship\Entity\Server;

interface ServerRepositoryInterface
{
    public function save(Server $server): void;

    public function delete(Server $server): void;

    public function findById(int $id): ?Server;

    /**
     * @return Server[]
     */
    public function findAll(): array;

    /**
     * @return Server[]
     */
    public function findActive(): array;
}
