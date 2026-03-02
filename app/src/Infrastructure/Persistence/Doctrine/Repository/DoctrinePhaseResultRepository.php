<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Championship\Entity\Phase;
use App\Domain\Championship\Entity\PhaseResult;
use App\Domain\Championship\Repository\PhaseResultRepositoryInterface;
use App\Domain\Player\Entity\Player;
use App\Domain\Team\Entity\Team;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrinePhaseResultRepository implements PhaseResultRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(PhaseResult $result): void
    {
        $this->entityManager->persist($result);
        $this->entityManager->flush();
    }

    public function delete(PhaseResult $result): void
    {
        $this->entityManager->remove($result);
        $this->entityManager->flush();
    }

    public function findById(int $id): ?PhaseResult
    {
        return $this->entityManager
            ->getRepository(PhaseResult::class)
            ->find($id);
    }

    public function findByPhaseAndPlayer(Phase $phase, Player $player): ?PhaseResult
    {
        return $this->entityManager
            ->getRepository(PhaseResult::class)
            ->findOneBy(['phase' => $phase, 'player' => $player]);
    }

    public function findByPhase(Phase $phase): array
    {
        return $this->entityManager
            ->getRepository(PhaseResult::class)
            ->findBy(['phase' => $phase]);
    }

    public function findByPhaseOrderedByPosition(Phase $phase): array
    {
        return $this->entityManager
            ->getRepository(PhaseResult::class)
            ->findBy(['phase' => $phase], ['position' => 'ASC']);
    }

    public function findByPhaseAndTeam(Phase $phase, Team $team): array
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('pr')
            ->from(PhaseResult::class, 'pr')
            ->join('pr.registration', 'reg')
            ->where('pr.phase = :phase')
            ->andWhere('reg.team = :team')
            ->setParameter('phase', $phase)
            ->setParameter('team', $team)
            ->orderBy('pr.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findQualifiedByPhase(Phase $phase): array
    {
        return $this->entityManager
            ->getRepository(PhaseResult::class)
            ->findBy(['phase' => $phase, 'isQualified' => true], ['position' => 'ASC']);
    }
}
