<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api;

use App\Application\Championship\Service\CompetitionStateServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final readonly class PlayerEligibilityController
{
    public function __construct(
        private CompetitionStateServiceInterface $competitionState,
    ) {
    }

    #[Route('/api/players/{login}/eligibility', name: 'api_players_eligibility', methods: ['GET'])]
    public function __invoke(string $login): JsonResponse
    {
        return new JsonResponse([
            'login' => $login,
            'isCompetitor' => $this->competitionState->isCompetitor($login),
        ]);
    }
}
