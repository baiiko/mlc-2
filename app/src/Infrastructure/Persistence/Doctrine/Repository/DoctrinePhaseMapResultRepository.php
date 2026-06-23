<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Championship\Entity\Phase;
use App\Domain\Championship\Entity\PhaseMapResult;
use App\Domain\Championship\Repository\PhaseMapResultRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrinePhaseMapResultRepository implements PhaseMapResultRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(PhaseMapResult $result): void
    {
        $this->entityManager->persist($result);
        $this->entityManager->flush();
    }

    public function findOneByUniqueKey(Phase $phase, string $mapUid, string $winner): ?PhaseMapResult
    {
        return $this->entityManager
            ->getRepository(PhaseMapResult::class)
            ->findOneBy(['phase' => $phase, 'mapUid' => $mapUid, 'winner' => $winner]);
    }

    public function hasWinner(Phase $phase, string $login): bool
    {
        return $this->entityManager
            ->getRepository(PhaseMapResult::class)
            ->count(['phase' => $phase, 'winner' => $login]) > 0;
    }
}
