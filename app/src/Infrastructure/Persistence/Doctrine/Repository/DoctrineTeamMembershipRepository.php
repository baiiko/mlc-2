<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Team\Entity\TeamMembership;
use App\Domain\Team\Repository\TeamMembershipRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineTeamMembershipRepository implements TeamMembershipRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(TeamMembership $membership): void
    {
        $this->entityManager->persist($membership);
        $this->entityManager->flush();
    }
}
