<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api;

use App\Domain\Championship\Entity\Phase;
use App\Domain\Championship\Repository\PhaseRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final readonly class PhaseRankingController
{
    public function __construct(
        private PhaseRepositoryInterface $phaseRepository,
    ) {
    }

    #[Route('/api/phases/{id}/ranking', name: 'api_phases_ranking', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function __invoke(int $id): JsonResponse
    {
        $phase = $this->phaseRepository->findById($id);

        if (!$phase instanceof Phase) {
            return new JsonResponse(['error' => 'Phase not found'], 404);
        }

        return new JsonResponse([
            'ranking' => $phase->getRanking() ?? [],
        ]);
    }
}
