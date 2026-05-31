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
    ) {
    }

    /**
     * @return array{
     *     online: bool,
     *     name: string|null,
     *     playerCount: int,
     *     maxPlayers: int,
     *     currentMap: string|null,
     *     players: array<string>,
     *     playerDetails: array<array{login: string, nickname: string}>,
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
     * @param Server[] $servers
     *
     * @return array<int, array{
     *     server: Server,
     *     info: array{online: bool, name: string|null, playerCount: int, maxPlayers: int, currentMap: string|null, players: array<string>, playerDetails: array<array{login: string, nickname: string}>, error: string|null}
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

    /**
     * Fetches the map list currently loaded on the TM server (GetMapList XML-RPC).
     *
     * @return array{
     *     online: bool,
     *     currentUid: string|null,
     *     maps: list<array{uid: string, name: string, fileName: string, author: string, environment: string}>,
     *     error: string|null,
     * }
     */
    public function getMapList(Server $server): array
    {
        $default = [
            'online' => false,
            'currentUid' => null,
            'maps' => [],
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

            $adminLogin = $server->getAdminLogin();
            $password = $server->getPassword();

            if ($adminLogin && $password && !$client->authenticate($adminLogin, $password)) {
                $default['error'] = 'Authentification échouée';
                $client->disconnect();

                return $default;
            }

            $rawMaps = $client->query('GetMapList', 1000, 0);

            if (!\is_array($rawMaps)) {
                $rawMaps = $client->query('GetChallengeList', 1000, 0);
            }

            $current = $client->query('GetCurrentChallengeInfo');

            if (!\is_array($current)) {
                $current = $client->query('GetCurrentMapInfo');
            }

            $client->disconnect();

            $currentUid = \is_array($current) ? ($current['UId'] ?? null) : null;
            $maps = [];

            if (\is_array($rawMaps)) {
                foreach ($rawMaps as $m) {
                    if (!\is_array($m)) {
                        continue;
                    }
                    $maps[] = [
                        'uid' => (string) ($m['UId'] ?? ''),
                        'name' => (string) ($m['Name'] ?? $m['NickName'] ?? ''),
                        'fileName' => (string) ($m['FileName'] ?? ''),
                        'author' => (string) ($m['Author'] ?? ''),
                        'environment' => (string) ($m['Environnement'] ?? $m['Environment'] ?? ''),
                    ];
                }
            }

            return [
                'online' => true,
                'currentUid' => $currentUid !== null ? (string) $currentUid : null,
                'maps' => $maps,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            $client->disconnect();
            $default['error'] = $e->getMessage();

            return $default;
        }
    }

    /**
     * @return array{
     *     online: bool,
     *     name: string|null,
     *     playerCount: int,
     *     maxPlayers: int,
     *     currentMap: string|null,
     *     players: array<string>,
     *     playerDetails: array<array{login: string, nickname: string}>,
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
            'playerDetails' => [],
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

            if (!\is_array($currentMap)) {
                $currentMap = $client->query('GetCurrentMapInfo');
            }
            $players = $client->query('GetPlayerList', 100, 0);

            $client->disconnect();

            // Get player list
            $playerList = [];
            $playerDetails = [];

            if (\is_array($players)) {
                foreach ($players as $player) {
                    if (isset($player['NickName'])) {
                        $playerList[] = $player['NickName'];
                        $playerDetails[] = [
                            'login' => isset($player['Login']) ? (string) $player['Login'] : '',
                            'nickname' => (string) $player['NickName'],
                        ];
                    }
                }
            }

            // Map name can be in 'Name' or 'NickName' depending on the game version
            $mapName = null;

            if (\is_array($currentMap)) {
                $mapName = $currentMap['Name'] ?? $currentMap['NickName'] ?? null;
            }

            return [
                'online' => true,
                'name' => \is_string($serverName) ? $serverName : $server->getName(),
                'playerCount' => \count($playerList),
                'maxPlayers' => isset($maxPlayers['CurrentValue']) ? (int) $maxPlayers['CurrentValue'] : $server->getMaxPlayers(),
                'currentMap' => $mapName,
                'players' => $playerList,
                'playerDetails' => $playerDetails,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            $client->disconnect();
            $default['error'] = $e->getMessage();

            return $default;
        }
    }
}
