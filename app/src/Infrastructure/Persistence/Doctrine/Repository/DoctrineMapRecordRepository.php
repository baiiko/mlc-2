<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Championship\Entity\MapRecord;
use App\Domain\Championship\Repository\MapRecordRepositoryInterface;
use App\Domain\Player\Entity\Player;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineMapRecordRepository implements MapRecordRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(int $id): ?MapRecord
    {
        return $this->entityManager
            ->getRepository(MapRecord::class)
            ->find($id);
    }

    public function findByMapUid(string $mapUid): array
    {
        return $this->entityManager
            ->getRepository(MapRecord::class)
            ->findBy(['mapUid' => $mapUid], ['laps' => 'ASC']);
    }

    public function findByMapUidWithPlayer(string $mapUid): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        $results = $qb->select('r', 'p.pseudo as playerPseudo')
            ->from(MapRecord::class, 'r')
            ->leftJoin(Player::class, 'p', 'WITH', 'p.login = r.playerLogin')
            ->where('r.mapUid = :mapUid')
            ->setParameter('mapUid', $mapUid)
            ->orderBy('r.laps', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(fn($row) => [
            'record' => $row[0],
            'playerPseudo' => $row['playerPseudo'],
        ], $results);
    }

    public function findByMapUidAndLaps(string $mapUid, int $laps): ?MapRecord
    {
        return $this->entityManager
            ->getRepository(MapRecord::class)
            ->findOneBy(['mapUid' => $mapUid, 'laps' => $laps]);
    }

    public function save(MapRecord $record): void
    {
        $this->entityManager->persist($record);
        $this->entityManager->flush();
    }

    public function remove(MapRecord $record): void
    {
        $this->entityManager->remove($record);
        $this->entityManager->flush();
    }
}
