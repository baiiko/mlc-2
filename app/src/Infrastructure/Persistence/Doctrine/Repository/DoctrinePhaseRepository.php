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

    public function findUpcomingPlayable(int $limit = 6): array
    {
        $now = new \DateTimeImmutable();

        return $this->entityManager
            ->createQueryBuilder()
            ->select('p')
            ->from(Phase::class, 'p')
            ->join('p.round', 'r')
            ->where('p.type != :registration')
            ->andWhere('p.startAt >= :now OR (p.startAt <= :now AND (p.endAt IS NULL OR p.endAt >= :now))')
            ->andWhere('r.deletedAt IS NULL')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('registration', PhaseType::Registration)
            ->setParameter('now', $now)
            ->orderBy('p.startAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findActiveQualificationPhase(): ?Phase
    {
        $now = new \DateTimeImmutable();

        return $this->entityManager
            ->createQueryBuilder()
            ->select('p')
            ->from(Phase::class, 'p')
            ->join('p.round', 'r')
            ->join('r.season', 's')
            ->where('p.type = :qualification')
            ->andWhere('p.startAt <= :now')
            ->andWhere('p.endAt IS NULL OR p.endAt >= :now')
            ->andWhere('r.deletedAt IS NULL')
            ->andWhere('p.deletedAt IS NULL')
            ->andWhere('s.isActive = true')
            ->setParameter('qualification', PhaseType::Qualification)
            ->setParameter('now', $now)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
