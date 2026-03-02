<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Championship\Entity\Round;
use App\Domain\Championship\Entity\Season;
use App\Domain\Championship\Repository\RoundRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineRoundRepository implements RoundRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Round $round): void
    {
        $this->entityManager->persist($round);
        $this->entityManager->flush();
    }

    public function delete(Round $round): void
    {
        $this->entityManager->remove($round);
        $this->entityManager->flush();
    }

    public function findById(int $id): ?Round
    {
        return $this->entityManager
            ->getRepository(Round::class)
            ->find($id);
    }

    public function findBySeason(Season $season): array
    {
        return $this->entityManager
            ->getRepository(Round::class)
            ->findBy(['season' => $season], ['number' => 'ASC']);
    }

    public function findActiveRounds(): array
    {
        return $this->entityManager
            ->getRepository(Round::class)
            ->findBy(['isActive' => true], ['number' => 'ASC']);
    }

    public function findWithOpenRegistration(): array
    {
        $now = new \DateTimeImmutable();

        return $this->entityManager
            ->createQueryBuilder()
            ->select('r')
            ->from(Round::class, 'r')
            ->where('r.registrationStartAt <= :now')
            ->andWhere('r.registrationEndAt >= :now')
            ->setParameter('now', $now)
            ->orderBy('r.registrationEndAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findCurrentOrUpcoming(): ?Round
    {
        // First, try to find the active round
        $activeRound = $this->entityManager
            ->getRepository(Round::class)
            ->findOneBy(['isActive' => true]);

        if ($activeRound !== null) {
            return $activeRound;
        }

        // Otherwise, find the next round with upcoming phases
        $now = new \DateTimeImmutable();

        return $this->entityManager
            ->createQueryBuilder()
            ->select('r')
            ->from(Round::class, 'r')
            ->join('r.phases', 'p')
            ->where('p.startAt >= :now')
            ->setParameter('now', $now)
            ->orderBy('p.startAt', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
