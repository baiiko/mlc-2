<?php

declare(strict_types=1);

namespace App\Application\Championship\Service;

use App\Domain\Championship\Entity\Server;
use App\Infrastructure\TrackMania\GbxRemote;

class ServerCommandService
{
    /**
     * @return array{success: bool, message: string}
     */
    public function toggleWarmUp(Server $server): array
    {
        return $this->executeCommand($server, function (GbxRemote $client): array {
            $currentWarmUp = $client->query('GetAllWarmUpDuration');

            if ($currentWarmUp === false) {
                return ['success' => false, 'message' => $client->getError() ?? 'Erreur inconnue'];
            }

            $isEnabled = ($currentWarmUp['CurrentValue'] ?? 0) === 1;
            $newValue = $isEnabled ? 0 : 1;

            $result = $client->query('SetAllWarmUpDuration', $newValue);

            if ($result === false) {
                return ['success' => false, 'message' => $client->getError() ?? 'Erreur inconnue'];
            }

            return [
                'success' => true,
                'message' => $newValue === 1 ? 'WarmUp activé' : 'WarmUp désactivé',
            ];
        });
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function sendChatMessage(Server $server, string $message): array
    {
        return $this->executeCommand($server, function (GbxRemote $client) use ($message): array {
            $result = $client->query('ChatSendServerMessage', $message);

            if ($result === false) {
                return ['success' => false, 'message' => $client->getError() ?? 'Erreur inconnue'];
            }

            return ['success' => true, 'message' => 'Message envoyé'];
        });
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function restartMap(Server $server): array
    {
        return $this->executeCommand($server, function (GbxRemote $client): array {
            $result = $client->query('RestartChallenge');
            if ($result === false) {
                $result = $client->query('RestartMap');
            }

            if ($result === false) {
                return ['success' => false, 'message' => $client->getError() ?? 'Erreur inconnue'];
            }

            return ['success' => true, 'message' => 'Map redémarrée'];
        });
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function skipMap(Server $server): array
    {
        return $this->executeCommand($server, function (GbxRemote $client): array {
            $result = $client->query('NextChallenge');
            if ($result === false) {
                $result = $client->query('NextMap');
            }

            if ($result === false) {
                return ['success' => false, 'message' => $client->getError() ?? 'Erreur inconnue'];
            }

            return ['success' => true, 'message' => 'Map suivante lancée'];
        });
    }

    /**
     * @param callable(GbxRemote): array{success: bool, message: string} $callback
     * @return array{success: bool, message: string}
     */
    private function executeCommand(Server $server, callable $callback): array
    {
        if (!$server->getIp() || !$server->getPort()) {
            return ['success' => false, 'message' => 'IP ou port non configuré'];
        }

        $client = new GbxRemote();

        try {
            if (!$client->connect($server->getIp(), $server->getPort(), 5)) {
                return ['success' => false, 'message' => $client->getError() ?? 'Connexion échouée'];
            }

            $adminLogin = $server->getAdminLogin();
            $password = $server->getPassword();

            if (!$adminLogin || !$password) {
                $client->disconnect();
                return ['success' => false, 'message' => 'Identifiants admin non configurés'];
            }

            if (!$client->authenticate($adminLogin, $password)) {
                $client->disconnect();
                return ['success' => false, 'message' => 'Authentification échouée'];
            }

            $result = $callback($client);

            $client->disconnect();

            return $result;
        } catch (\Throwable $e) {
            $client->disconnect();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
