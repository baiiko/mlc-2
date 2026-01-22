<?php

declare(strict_types=1);

namespace App\Application\Championship\Service;

interface ServerApiDataServiceInterface
{
    /**
     * Get formatted server data for API response.
     *
     * @return array<array{
     *     id: int,
     *     login: ?string,
     *     name: ?string,
     *     nameHtml: string,
     *     nameStrip: string,
     *     online: bool,
     *     playerCount: int,
     *     maxPlayers: int,
     *     currentMap: ?string,
     *     currentMapHtml: ?string,
     *     currentMapStrip: ?string,
     *     players: array
     * }>
     */
    public function getServersApiData(): array;
}
