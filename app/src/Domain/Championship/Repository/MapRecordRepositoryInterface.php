<?php

declare(strict_types=1);

namespace App\Domain\Championship\Repository;

use App\Domain\Championship\Entity\MapRecord;
use App\Domain\Championship\Enum\GameMode;

interface MapRecordRepositoryInterface
{
    public function findById(int $id): ?MapRecord;

    /**
     * @return array<MapRecord>
     */
    public function findByMapUid(string $mapUid): array;

    /**
     * Returns records with player pseudo if available.
     * @return array<array{record: MapRecord, playerPseudo: ?string}>
     */
    public function findByMapUidWithPlayer(string $mapUid, ?int $roundId = null): array;

    public function findByMapUidAndLaps(string $mapUid, int $laps): ?MapRecord;

    public function save(MapRecord $record): void;

    public function remove(MapRecord $record): void;

    /**
     * Deletes all records for a player.
     * @return int Number of deleted records
     */
    public function deleteByPlayerLogin(string $playerLogin): int;

    /**
     * Returns all records for a map grouped by laps, sorted by time.
     * @return array<int, array<array{record: MapRecord, playerPseudo: ?string}>>
     */
    public function findRankingsByMapUid(string $mapUid): array;

    /**
     * Returns records grouped by round.
     * @return array<int, array<array{record: MapRecord, playerPseudo: ?string}>>
     */
    public function findRankingsByMapUidGroupedByRound(string $mapUid): array;

    /**
     * Returns best time per player for each game mode.
     * @return array<int, array<array{record: MapRecord, playerPseudo: ?string}>>
     */
    public function findBestRankingsByMapUidPerGameMode(string $mapUid): array;

    /**
     * Returns the best single lap record for a map (all game modes combined).
     * @return array{record: MapRecord, playerPseudo: ?string}|null
     */
    public function findBestLapRecord(string $mapUid): ?array;
}
