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

    public function findByMapUidWithPlayer(string $mapUid, ?int $roundId = null): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('r', 'p.pseudo as playerPseudo')
            ->from(MapRecord::class, 'r')
            ->leftJoin(Player::class, 'p', 'WITH', 'p.login = r.playerLogin')
            ->where('r.mapUid = :mapUid')
            ->setParameter('mapUid', $mapUid)
            ->orderBy('r.laps', 'ASC')
            ->addOrderBy('r.time', 'ASC');

        if ($roundId !== null) {
            $qb->andWhere('r.roundId = :roundId')
                ->setParameter('roundId', $roundId);
        }

        $results = $qb->getQuery()->getResult();

        return array_map(fn (array $row): array => [
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

    public function deleteByPlayerLogin(string $playerLogin): int
    {
        return $this->entityManager->createQueryBuilder()
            ->delete(MapRecord::class, 'r')
            ->where('r.playerLogin = :playerLogin')
            ->setParameter('playerLogin', $playerLogin)
            ->getQuery()
            ->execute();
    }

    public function updatePlayerLogin(string $oldLogin, string $newLogin): int
    {
        return $this->entityManager->createQueryBuilder()
            ->update(MapRecord::class, 'r')
            ->set('r.playerLogin', ':newLogin')
            ->where('r.playerLogin = :oldLogin')
            ->setParameter('oldLogin', $oldLogin)
            ->setParameter('newLogin', $newLogin)
            ->getQuery()
            ->execute();
    }

    public function findRankingsByMapUid(string $mapUid): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        $results = $qb->select('r', 'p.pseudo as playerPseudo')
            ->from(MapRecord::class, 'r')
            ->leftJoin(Player::class, 'p', 'WITH', 'p.login = r.playerLogin')
            ->where('r.mapUid = :mapUid')
            ->setParameter('mapUid', $mapUid)
            ->orderBy('r.laps', 'ASC')
            ->addOrderBy('r.time', 'ASC')
            ->getQuery()
            ->getResult();

        $rankings = [];

        foreach ($results as $row) {
            $record = $row[0];
            $laps = $record->getLaps();

            if (!isset($rankings[$laps])) {
                $rankings[$laps] = [];
            }
            $rankings[$laps][] = [
                'record' => $record,
                'playerPseudo' => $row['playerPseudo'],
            ];
        }

        return $rankings;
    }

    public function findRankingsByMapUidGroupedByRound(string $mapUid): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        $results = $qb->select('r', 'p.pseudo as playerPseudo')
            ->from(MapRecord::class, 'r')
            ->leftJoin(Player::class, 'p', 'WITH', 'p.login = r.playerLogin')
            ->where('r.mapUid = :mapUid')
            ->andWhere('r.roundId IS NOT NULL')
            ->setParameter('mapUid', $mapUid)
            ->orderBy('r.roundId', 'ASC')
            ->addOrderBy('r.laps', 'ASC')
            ->addOrderBy('r.time', 'ASC')
            ->getQuery()
            ->getResult();

        // Group by roundId -> laps
        $rankings = [];

        foreach ($results as $row) {
            $record = $row[0];
            $roundId = $record->getRoundId();
            $laps = $record->getLaps();

            if (!isset($rankings[$roundId])) {
                $rankings[$roundId] = [];
            }

            if (!isset($rankings[$roundId][$laps])) {
                $rankings[$roundId][$laps] = [];
            }

            $rankings[$roundId][$laps][] = [
                'record' => $record,
                'playerPseudo' => $row['playerPseudo'],
            ];
        }

        return $rankings;
    }

    public function findBestRankingsByMapUidPerGameMode(string $mapUid): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        // Get all records for this map
        $results = $qb->select('r', 'p.pseudo as playerPseudo')
            ->from(MapRecord::class, 'r')
            ->leftJoin(Player::class, 'p', 'WITH', 'p.login = r.playerLogin')
            ->where('r.mapUid = :mapUid')
            ->setParameter('mapUid', $mapUid)
            ->orderBy('r.gameMode', 'ASC')
            ->addOrderBy('r.laps', 'ASC')
            ->addOrderBy('r.time', 'ASC')
            ->getQuery()
            ->getResult();

        // Group by game mode -> laps, keeping only best time per player
        $rankings = [];
        $seenPlayers = [];

        foreach ($results as $row) {
            $record = $row[0];
            $gameMode = $record->getGameMode()->value;
            $laps = $record->getLaps();
            $playerLogin = $record->getPlayerLogin();

            if (!isset($rankings[$gameMode])) {
                $rankings[$gameMode] = [];
                $seenPlayers[$gameMode] = [];
            }

            if (!isset($rankings[$gameMode][$laps])) {
                $rankings[$gameMode][$laps] = [];
                $seenPlayers[$gameMode][$laps] = [];
            }

            // Only keep first (best) record per player per game mode per laps
            if (!isset($seenPlayers[$gameMode][$laps][$playerLogin])) {
                $seenPlayers[$gameMode][$laps][$playerLogin] = true;
                $rankings[$gameMode][$laps][] = [
                    'record' => $record,
                    'playerPseudo' => $row['playerPseudo'],
                ];
            }
        }

        return $rankings;
    }

    public function findBestLapRecord(string $mapUid): ?array
    {
        $qb = $this->entityManager->createQueryBuilder();

        $result = $qb->select('r', 'p.pseudo as playerPseudo')
            ->from(MapRecord::class, 'r')
            ->leftJoin(Player::class, 'p', 'WITH', 'p.login = r.playerLogin')
            ->where('r.mapUid = :mapUid')
            ->andWhere('r.laps = 1')
            ->setParameter('mapUid', $mapUid)
            ->orderBy('r.time', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        if (empty($result)) {
            return null;
        }

        return [
            'record' => $result[0][0],
            'playerPseudo' => $result[0]['playerPseudo'],
        ];
    }
}
