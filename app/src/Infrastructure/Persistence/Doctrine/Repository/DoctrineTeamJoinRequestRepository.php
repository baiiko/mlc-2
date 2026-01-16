<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Player\Entity\Player;
use App\Domain\Team\Entity\JoinRequestStatus;
use App\Domain\Team\Entity\Team;
use App\Domain\Team\Entity\TeamJoinRequest;
use App\Domain\Team\Repository\TeamJoinRequestRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineTeamJoinRequestRepository implements TeamJoinRequestRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(TeamJoinRequest $request): void
    {
        $this->entityManager->persist($request);
        $this->entityManager->flush();
    }

    public function delete(TeamJoinRequest $request): void
    {
        $this->entityManager->remove($request);
        $this->entityManager->flush();
    }

    public function findById(int $id): ?TeamJoinRequest
    {
        return $this->entityManager
            ->getRepository(TeamJoinRequest::class)
            ->find($id);
    }

    public function findPendingByPlayer(Player $player): ?TeamJoinRequest
    {
        return $this->entityManager
            ->getRepository(TeamJoinRequest::class)
            ->findOneBy([
                'player' => $player,
                'status' => JoinRequestStatus::Pending,
            ]);
    }

    public function findPendingByPlayerAndTeam(Player $player, Team $team): ?TeamJoinRequest
    {
        return $this->entityManager
            ->getRepository(TeamJoinRequest::class)
            ->findOneBy([
                'player' => $player,
                'team' => $team,
                'status' => JoinRequestStatus::Pending,
            ]);
    }

    public function findPendingByTeam(Team $team): array
    {
        return $this->entityManager
            ->getRepository(TeamJoinRequest::class)
            ->findBy(
                ['team' => $team, 'status' => JoinRequestStatus::Pending],
                ['createdAt' => 'ASC']
            );
    }
}
