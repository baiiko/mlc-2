<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api;

use App\Application\Championship\Service\CompetitionStateServiceInterface;
use App\Domain\Championship\Entity\PhaseType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final readonly class ActivePhaseController
{
    public function __construct(
        private CompetitionStateServiceInterface $competitionState,
    ) {
    }

    #[Route('/api/phases/active', name: 'api_phases_active', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $rawType = $request->query->get('type');
        $type = null;

        if ($rawType !== null && $rawType !== '') {
            $type = PhaseType::tryFrom((string) $rawType);

            if ($type === null) {
                return new JsonResponse(['error' => 'Invalid type'], 400);
            }
        }

        $group = $request->query->has('group') ? $request->query->getInt('group') : null;

        return new JsonResponse([
            'phase' => $this->competitionState->phaseToArray(
                $this->competitionState->getActivePhase($type, $group),
            ),
        ]);
    }
}
