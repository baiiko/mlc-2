<?php

declare(strict_types=1);

namespace App\Application\Championship\Service;

use App\Domain\Championship\Repository\ServerRepositoryInterface;
use App\Infrastructure\Service\TmColorParser;

final readonly class ServerApiDataService implements ServerApiDataServiceInterface
{
    public function __construct(
        private ServerRepositoryInterface $serverRepository,
        private ServerInfoService $serverInfoService,
    ) {
    }

    public function getServersApiData(): array
    {
        $servers = $this->serverRepository->findActive();
        $serversInfo = $this->serverInfoService->getMultipleServersInfo($servers);

        $data = [];
        foreach ($serversInfo as $serverData) {
            $server = $serverData['server'];
            $info = $serverData['info'];

            $data[] = [
                'id' => $server->getId(),
                'login' => $server->getLogin(),
                'name' => $info['name'],
                'nameHtml' => TmColorParser::toHtml($info['name'] ?? ''),
                'nameStrip' => TmColorParser::stripColors($info['name'] ?? ''),
                'online' => $info['online'],
                'playerCount' => $info['playerCount'],
                'maxPlayers' => $info['maxPlayers'],
                'currentMap' => $info['currentMap'],
                'currentMapHtml' => $info['currentMap'] ? TmColorParser::toHtml($info['currentMap']) : null,
                'currentMapStrip' => $info['currentMap'] ? TmColorParser::stripColors($info['currentMap']) : null,
                'players' => array_map(fn($p) => [
                    'name' => $p,
                    'nameHtml' => TmColorParser::toHtml($p),
                ], $info['players']),
            ];
        }

        return $data;
    }
}
