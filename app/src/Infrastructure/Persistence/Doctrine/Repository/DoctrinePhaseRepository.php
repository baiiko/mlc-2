<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Championship\Entity\Phase;
use App\Domain\Championship\Entity\PhaseType;
use App\Domain\Championship\Entity\Round;
use App\Domain\Championship\Repository\PhaseRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrinePhaseRepository implements PhaseRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Phase $phase): void
    {
        $this->entityManager->persist($phase);
        $this->entityManager->flush();
    }

    public function delete(Phase $phase): void
    {
        $this->entityManager->remove($phase);
        $this->entityManager->flush();
    }

    public function findById(int $id): ?Phase
    {
        return $this->entityManager
            ->getRepository(Phase::class)
            ->find($id);
    }

    public function findByRound(Round $round): array
    {
        return $this->entityManager
            ->getRepository(Phase::class)
            ->findBy(['round' => $round], ['startAt' => 'ASC']);
    }

    public function findByRoundAndType(Round $round, PhaseType $type): ?Phase
    {
        return $this->entityManager
            ->getRepository(Phase::class)
            ->findOneBy(['round' => $round, 'type' => $type]);
    }
}
