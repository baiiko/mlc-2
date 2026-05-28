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

    public function findByIdIncludingDeleted(int $id): ?Team
    {
        $filters = $this->entityManager->getFilters();
        $wasEnabled = $filters->isEnabled('softdeleteable');

        if ($wasEnabled) {
            $filters->disable('softdeleteable');
        }

        try {
            $team = $this->entityManager->getRepository(Team::class)->find($id);

            // find() may return an already-cached, uninitialized proxy without hitting
            // the DB. Force the load now, while the soft-delete filter is still disabled,
            // otherwise the lazy-load fires later with the filter back on and throws.
            if ($team !== null) {
                $this->entityManager->initializeObject($team);
            }

            return $team;
        } finally {
            if ($wasEnabled) {
                $filters->enable('softdeleteable');
            }
        }
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
