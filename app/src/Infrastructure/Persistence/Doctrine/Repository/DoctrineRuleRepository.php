<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Content\Entity\Rule;
use App\Domain\Content\Repository\RuleRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineRuleRepository implements RuleRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findLatest(): ?Rule
    {
        return $this->entityManager
            ->getRepository(Rule::class)
            ->findOneBy([], ['createdAt' => 'DESC']);
    }
}
