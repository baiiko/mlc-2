<?php

declare(strict_types=1);

namespace App\Application\Championship\Service;

use App\Domain\Championship\Entity\Server;
use App\Infrastructure\TrackMania\GbxRemote;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class ServerInfoService
{
    public function __construct(
        private readonly CacheInterface $cache,
    ) {}

    /**
     * @return array{
     *     online: bool,
     *     name: string|null,
     *     playerCount: int,
     *     maxPlayers: int,
     *     currentMap: string|null,
     *     players: array<string>,
     *     error: string|null
     * }
     */
    public function getServerInfo(Server $server): array
    {
        $cacheKey = 'server_info_' . $server->getId();

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($server): array {
            $item->expiresAfter(5); // Cache for 5 seconds

            return $this->fetchServerInfo($server);
        });
    }

    /**
     * @return array{
     *     online: bool,
     *     name: string|null,
     *     playerCount: int,
     *     maxPlayers: int,
     *     currentMap: string|null,
     *     players: array<string>,
     *     error: string|null
     * }
     */
    private function fetchServerInfo(Server $server): array
    {
        $default = [
            'online' => false,
            'name' => $server->getName(),
            'playerCount' => 0,
            'maxPlayers' => $server->getMaxPlayers(),
            'currentMap' => null,
            'players' => [],
            'error' => null,
        ];

        if (!$server->getIp() || !$server->getPort()) {
            $default['error'] = 'IP ou port non configuré';
            return $default;
        }

        $client = new GbxRemote();

        try {
            if (!$client->connect($server->getIp(), $server->getPort(), 2)) {
                $default['error'] = $client->getError();
                return $default;
            }

            // Authenticate with admin account
            $adminLogin = $server->getAdminLogin();
            $password = $server->getPassword();
            if ($adminLogin && $password && !$client->authenticate($adminLogin, $password)) {
                $default['error'] = 'Authentification échouée';
                $client->disconnect();
                return $default;
            }

            // Get server info
            $serverName = $client->query('GetServerName');
            $maxPlayers = $client->query('GetMaxPlayers');
            // TMNF uses GetCurrentChallengeInfo, TM2/TM2020 uses GetCurrentMapInfo
            $currentMap = $client->query('GetCurrentChallengeInfo');
            if (!is_array($currentMap)) {
                $currentMap = $client->query('GetCurrentMapInfo');
            }
            $players = $client->query('GetPlayerList', 100, 0);

            $client->disconnect();

            // Get player list
            $playerList = [];
            if (is_array($players)) {
                foreach ($players as $player) {
                    if (isset($player['NickName'])) {
                        $playerList[] = $player['NickName'];
                    }
                }
            }

            // Map name can be in 'Name' or 'NickName' depending on the game version
            $mapName = null;
            if (is_array($currentMap)) {
                $mapName = $currentMap['Name'] ?? $currentMap['NickName'] ?? null;
            }

            return [
                'online' => true,
                'name' => is_string($serverName) ? $serverName : $server->getName(),
                'playerCount' => count($playerList),
                'maxPlayers' => isset($maxPlayers['CurrentValue']) ? (int) $maxPlayers['CurrentValue'] : $server->getMaxPlayers(),
                'currentMap' => $mapName,
                'players' => $playerList,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            $client->disconnect();
            $default['error'] = $e->getMessage();
            return $default;
        }
    }

    /**
     * @param Server[] $servers
     * @return array<int, array{
     *     server: Server,
     *     info: array{online: bool, name: string|null, playerCount: int, maxPlayers: int, currentMap: string|null, players: array<string>, error: string|null}
     * }>
     */
    public function getMultipleServersInfo(array $servers): array
    {
        $results = [];

        foreach ($servers as $server) {
            $results[] = [
                'server' => $server,
                'info' => $this->getServerInfo($server),
            ];
        }

        return $results;
    }
}
