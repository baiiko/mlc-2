<?php

declare(strict_types=1);

namespace App\Domain\Championship\Repository;

use App\Domain\Championship\Entity\MapRecord;

interface MapRecordRepositoryInterface
{
    public function findById(int $id): ?MapRecord;

    /**
     * @return array<MapRecord>
     */
    public function findByMapUid(string $mapUid): array;

    /**
     * Returns records with player pseudo if available.
     *
     * @return array<array{record: MapRecord, playerPseudo: ?string}>
     */
    public function findByMapUidWithPlayer(string $mapUid, ?int $roundId = null): array;

    public function findByMapUidAndLaps(string $mapUid, int $laps): ?MapRecord;

    public function save(MapRecord $record): void;

    public function remove(MapRecord $record): void;

    /**
     * Deletes all records for a player.
     *
     * @return int Number of deleted records
     */
    public function deleteByPlayerLogin(string $playerLogin): int;

    /**
     * Updates all records from one login to another.
     *
     * @return int Number of updated records
     */
    public function updatePlayerLogin(string $oldLogin, string $newLogin): int;

    /**
     * Migrates all records of a given map UID to a new UID, scoped to a single round.
     *
     * @return int Number of updated records
     */
    public function updateMapUidForRound(string $oldUid, string $newUid, int $roundId): int;

    /**
     * Attaches orphaned records (round_id IS NULL) of a player to the given round,
     * scoped to maps that actually belong to that round (via round_map.uid).
     *
     * @return int Number of attached records
     */
    public function attachOrphanRecordsToRound(string $playerLogin, int $roundId): int;

    /**
     * Returns all records for a map grouped by laps, sorted by time.
     *
     * @return array<int, array<array{record: MapRecord, playerPseudo: ?string}>>
     */
    public function findRankingsByMapUid(string $mapUid): array;

    /**
     * Returns records grouped by round.
     *
     * @return array<int, array<array{record: MapRecord, playerPseudo: ?string}>>
     */
    public function findRankingsByMapUidGroupedByRound(string $mapUid): array;

    /**
     * Returns best time per player for each game mode.
     *
     * @return array<int, array<array{record: MapRecord, playerPseudo: ?string}>>
     */
    public function findBestRankingsByMapUidPerGameMode(string $mapUid): array;

    /**
     * Returns the best single lap record for a map (all game modes combined).
     *
     * @return array{record: MapRecord, playerPseudo: ?string}|null
     */
    public function findBestLapRecord(string $mapUid): ?array;

    /**
     * For every given map UID and every requested laps count, return the single best
     * record (player + time). Result is keyed by map UID; each map maps to a flat list
     * of {record, playerPseudo} entries — one per laps when records exist.
     *
     * @param string[] $mapUids
     * @param int[]    $lapsList
     *
     * @return array<string, list<array{record: MapRecord, playerPseudo: ?string}>>
     */
    public function findBestRecordsByLapsForMapUids(array $mapUids, array $lapsList = [1, 5, 10]): array;
}
