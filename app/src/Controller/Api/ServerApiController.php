<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Application\Championship\Service\ServerApiDataServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route('/api')]
final readonly class ServerApiController
{
    public function __construct(
        private ServerApiDataServiceInterface $serverApiDataService,
    ) {
    }

    #[Route('/servers', name: 'api_servers', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return new JsonResponse($this->serverApiDataService->getServersApiData());
    }
}
