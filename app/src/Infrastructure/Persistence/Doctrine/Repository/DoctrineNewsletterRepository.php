<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Communication\Entity\Newsletter;
use App\Domain\Communication\Repository\NewsletterRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineNewsletterRepository implements NewsletterRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Newsletter $newsletter): void
    {
        $this->entityManager->persist($newsletter);
        $this->entityManager->flush();
    }

    public function findById(int $id): ?Newsletter
    {
        return $this->entityManager
            ->getRepository(Newsletter::class)
            ->find($id);
    }
}
