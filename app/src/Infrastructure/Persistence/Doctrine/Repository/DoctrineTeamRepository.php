<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Team\Entity\Team;
use App\Domain\Team\Repository\TeamRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineTeamRepository implements TeamRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Team $team): void
    {
        $this->entityManager->persist($team);
        $this->entityManager->flush();
    }

    public function delete(Team $team): void
    {
        $this->entityManager->remove($team);
        $this->entityManager->flush();
    }

    public function findById(int $id): ?Team
    {
        return $this->entityManager
            ->getRepository(Team::class)
            ->find($id);
    }

    public function findByTag(string $tag): ?Team
    {
        return $this->entityManager
            ->getRepository(Team::class)
            ->findOneBy(['tag' => $tag]);
    }

    public function findAll(): array
    {
        return $this->entityManager
            ->getRepository(Team::class)
            ->findBy([], ['tag' => 'ASC']);
    }
}
