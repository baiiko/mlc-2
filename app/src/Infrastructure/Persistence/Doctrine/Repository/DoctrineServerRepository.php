<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Championship\Entity\Server;
use App\Domain\Championship\Repository\ServerRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineServerRepository implements ServerRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Server $server): void
    {
        $this->entityManager->persist($server);
        $this->entityManager->flush();
    }

    public function delete(Server $server): void
    {
        $this->entityManager->remove($server);
        $this->entityManager->flush();
    }

    public function findById(int $id): ?Server
    {
        return $this->entityManager
            ->getRepository(Server::class)
            ->find($id);
    }

    public function findAll(): array
    {
        return $this->entityManager
            ->getRepository(Server::class)
            ->findBy([], ['name' => 'ASC']);
    }

    public function findActive(): array
    {
        return $this->entityManager
            ->getRepository(Server::class)
            ->findBy(['isActive' => true], ['name' => 'ASC']);
    }
}
