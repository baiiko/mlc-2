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
final readonly class PhaseEndedController
{
    public function __construct(
        private CompetitionStateServiceInterface $competitionState,
    ) {
    }

    #[Route('/api/phases/ended', name: 'api_phases_ended', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $type = PhaseType::tryFrom((string) $request->query->get('type', ''));

        if ($type === null) {
            return new JsonResponse(['error' => 'Invalid or missing type'], 400);
        }

        $group = $request->query->has('group') ? $request->query->getInt('group') : null;

        return new JsonResponse([
            'ended' => $this->competitionState->isEnded($type, $group),
        ]);
    }
}
