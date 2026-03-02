<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Championship\Entity\Round;
use App\Domain\Championship\Entity\RoundRegistration;
use App\Domain\Championship\Repository\RoundRegistrationRepositoryInterface;
use App\Domain\Player\Entity\Player;
use App\Domain\Team\Entity\Team;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineRoundRegistrationRepository implements RoundRegistrationRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(RoundRegistration $registration): void
    {
        $this->entityManager->persist($registration);
        $this->entityManager->flush();
    }

    public function delete(RoundRegistration $registration): void
    {
        $this->entityManager->remove($registration);
        $this->entityManager->flush();
    }

    public function findById(int $id): ?RoundRegistration
    {
        return $this->entityManager
            ->getRepository(RoundRegistration::class)
            ->find($id);
    }

    public function findByRoundAndPlayer(Round $round, Player $player): ?RoundRegistration
    {
        return $this->entityManager
            ->getRepository(RoundRegistration::class)
            ->findOneBy(['round' => $round, 'player' => $player]);
    }

    public function findByRound(Round $round): array
    {
        return $this->entityManager
            ->getRepository(RoundRegistration::class)
            ->findBy(['round' => $round], ['registeredAt' => 'ASC']);
    }

    public function findByRoundAndTeam(Round $round, Team $team): array
    {
        return $this->entityManager
            ->getRepository(RoundRegistration::class)
            ->findBy(['round' => $round, 'team' => $team], ['registeredAt' => 'ASC']);
    }

    public function countByRound(Round $round): int
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from(RoundRegistration::class, 'r')
            ->where('r.round = :round')
            ->setParameter('round', $round)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByRoundAndTeam(Round $round, Team $team): int
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from(RoundRegistration::class, 'r')
            ->where('r.round = :round')
            ->andWhere('r.team = :team')
            ->setParameter('round', $round)
            ->setParameter('team', $team)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
