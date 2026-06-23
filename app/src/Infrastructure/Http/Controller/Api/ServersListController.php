<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api;

use App\Domain\Championship\Repository\ServerRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * MLC server registry for the game server ("yuggy"), which drops its last local
 * SQL table (`server`). See mlc-server/docs/mlc-site-servers-endpoint.md.
 *
 * Distinct from the legacy front-facing `api_servers` (/{_locale}/api/servers,
 * rich shape) — this one is served at /api/servers (no locale) with the minimal
 * registry shape the addon needs.
 */
#[AsController]
final readonly class ServersListController
{
    public function __construct(
        private ServerRepositoryInterface $serverRepository,
    ) {
    }

    #[Route('/api/servers', name: 'api_servers_list', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $servers = array_map(static fn ($server): array => [
            'login' => $server->getLogin(),
            'name' => $server->getName(),
            'purpose' => $server->getPurpose()->value,
        ], $this->serverRepository->findActive());

        return new JsonResponse(['servers' => $servers]);
    }
}
