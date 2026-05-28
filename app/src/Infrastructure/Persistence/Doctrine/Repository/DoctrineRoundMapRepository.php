<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Championship\Entity\RoundMap;
use App\Domain\Championship\Repository\RoundMapRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

final readonly class DoctrineRoundMapRepository implements RoundMapRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findPaginated(
        int $page,
        int $perPage = 30,
        ?string $search = null,
        ?int $roundId = null,
        ?int $seasonId = null,
        ?string $author = null,
    ): array {
        $qb = $this->buildFilteredQb($search, $roundId, $seasonId, $author)
            ->select('m', 'r', 's')
            ->orderBy('m.createdAt', 'DESC')
            ->setFirstResult(max(0, ($page - 1) * $perPage))
            ->setMaxResults($perPage);

        return $qb->getQuery()->getResult();
    }

    public function countAll(
        ?string $search = null,
        ?int $roundId = null,
        ?int $seasonId = null,
        ?string $author = null,
    ): int {
        return (int) $this->buildFilteredQb($search, $roundId, $seasonId, $author)
            ->select('COUNT(m.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function buildFilteredQb(
        ?string $search,
        ?int $roundId,
        ?int $seasonId,
        ?string $author,
    ): QueryBuilder {
        $qb = $this->entityManager
            ->createQueryBuilder()
            ->from(RoundMap::class, 'm')
            ->leftJoin('m.round', 'r')
            ->leftJoin('r.season', 's');

        if ($search !== null && $search !== '') {
            $qb->andWhere('m.name LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($author !== null && $author !== '') {
            $qb->andWhere('m.author LIKE :author')
                ->setParameter('author', '%' . $author . '%');
        }

        if ($roundId !== null) {
            $qb->andWhere('r.id = :roundId')
                ->setParameter('roundId', $roundId);
        }

        if ($seasonId !== null) {
            $qb->andWhere('s.id = :seasonId')
                ->setParameter('seasonId', $seasonId);
        }

        return $qb;
    }
}
