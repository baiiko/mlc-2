<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api;

use App\Domain\Championship\Entity\Phase;
use App\Domain\Championship\Repository\PhaseMapResultRepositoryInterface;
use App\Domain\Championship\Repository\PhaseRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final readonly class PhaseHasWonController
{
    public function __construct(
        private PhaseRepositoryInterface $phaseRepository,
        private PhaseMapResultRepositoryInterface $phaseMapResultRepository,
    ) {
    }

    #[Route('/api/phases/{id}/has-won', name: 'api_phases_has_won', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function __invoke(int $id, Request $request): JsonResponse
    {
        $login = $request->query->get('login');

        if (!\is_string($login) || $login === '') {
            return new JsonResponse(['error' => 'Missing login'], 400);
        }

        $phase = $this->phaseRepository->findById($id);

        if (!$phase instanceof Phase) {
            return new JsonResponse(['error' => 'Phase not found'], 404);
        }

        return new JsonResponse([
            'hasWon' => $this->phaseMapResultRepository->hasWinner($phase, $login),
        ]);
    }
}
