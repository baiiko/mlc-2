<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Championship\Entity\Season;
use App\Domain\Championship\Repository\SeasonRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineSeasonRepository implements SeasonRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Season $season): void
    {
        $this->entityManager->persist($season);
        $this->entityManager->flush();
    }

    public function delete(Season $season): void
    {
        $this->entityManager->remove($season);
        $this->entityManager->flush();
    }

    public function findById(int $id): ?Season
    {
        return $this->entityManager
            ->getRepository(Season::class)
            ->find($id);
    }

    public function findBySlug(string $slug): ?Season
    {
        return $this->entityManager
            ->getRepository(Season::class)
            ->findOneBy(['slug' => $slug]);
    }

    public function findActive(): ?Season
    {
        return $this->entityManager
            ->getRepository(Season::class)
            ->findOneBy(['isActive' => true]);
    }

    public function findAll(): array
    {
        return $this->entityManager
            ->getRepository(Season::class)
            ->findBy([], ['startDate' => 'DESC']);
    }

    public function findAllActive(): array
    {
        return $this->entityManager
            ->getRepository(Season::class)
            ->findBy(['isActive' => true], ['startDate' => 'DESC']);
    }
}
