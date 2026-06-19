<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api;

use App\Application\Championship\Service\CompetitionStateServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final readonly class ServerContextController
{
    public function __construct(
        private CompetitionStateServiceInterface $competitionState,
    ) {
    }

    #[Route('/api/servers/{login}/context', name: 'api_servers_context', methods: ['GET'])]
    public function __invoke(string $login): JsonResponse
    {
        $context = $this->competitionState->getServerContext($login);

        if ($context === null) {
            return new JsonResponse(['error' => 'Server not found'], 404);
        }

        return new JsonResponse($context);
    }
}
