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
     * @return array<array{record: MapRecord, playerPseudo: ?string}>
     */
    public function findByMapUidWithPlayer(string $mapUid): array;

    public function findByMapUidAndLaps(string $mapUid, int $laps): ?MapRecord;

    public function save(MapRecord $record): void;

    public function remove(MapRecord $record): void;
}
