<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Championship\Entity\RoundMap;
use App\Domain\Championship\Repository\RoundMapRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineRoundMapRepository implements RoundMapRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findPaginated(int $page, int $perPage = 30, ?string $search = null): array
    {
        $qb = $this->entityManager
            ->createQueryBuilder()
            ->select('m', 'r', 's')
            ->from(RoundMap::class, 'm')
            ->leftJoin('m.round', 'r')
            ->leftJoin('r.season', 's')
            ->orderBy('m.createdAt', 'DESC')
            ->setFirstResult(max(0, ($page - 1) * $perPage))
            ->setMaxResults($perPage);

        if ($search !== null && $search !== '') {
            $qb->andWhere('m.name LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        return $qb->getQuery()->getResult();
    }

    public function countAll(?string $search = null): int
    {
        $qb = $this->entityManager
            ->createQueryBuilder()
            ->select('COUNT(m.id)')
            ->from(RoundMap::class, 'm');

        if ($search !== null && $search !== '') {
            $qb->andWhere('m.name LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
