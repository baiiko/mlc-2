<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api;

use App\Application\Championship\Service\CompetitionStateServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final readonly class ActiveRoundController
{
    public function __construct(
        private CompetitionStateServiceInterface $competitionState,
    ) {
    }

    #[Route('/api/rounds/active', name: 'api_rounds_active', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse([
            'round' => $this->competitionState->roundToArray($this->competitionState->getActiveRound()),
        ]);
    }
}
