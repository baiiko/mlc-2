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
        $result = $this->sendChatMessage($server, '/warmup');

        if ($result['success']) {
            return ['success' => true, 'message' => 'Commande /warmup envoyée au plugin serveur.'];
        }

        return $result;
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
    /**
     * Trigger the server-side plugin to reload its match settings by sending the
     * "/phase {type}" chat command. The plugin handles the actual .xml reload.
     *
     * @return array{success: bool, message: string}
     */
    public function reloadMatchSettings(Server $server, string $phaseName = 'qualification'): array
    {
        $result = $this->sendChatMessage($server, '/phase ' . $phaseName);

        if ($result['success']) {
            return ['success' => true, 'message' => \sprintf('Phase "%s" demandée au plugin serveur.', $phaseName)];
        }

        return $result;
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function rebootServer(Server $server): array
    {
        return $this->executeCommand($server, function (GbxRemote $client): array {
            $result = $client->query('QuitGame');

            if ($result === false) {
                return ['success' => false, 'message' => $client->getError() ?? 'Erreur inconnue'];
            }

            return ['success' => true, 'message' => 'Serveur en cours de redémarrage…'];
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
     *
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
