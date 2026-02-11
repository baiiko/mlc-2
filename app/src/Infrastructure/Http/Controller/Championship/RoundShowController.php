<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Championship;

use App\Domain\Championship\Repository\RoundRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsController]
final readonly class RoundShowController
{
    public function __construct(
        private RoundRepositoryInterface $roundRepository,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/championship/round/{id}', name: 'app_championship_round_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function __invoke(int $id): RedirectResponse
    {
        $round = $this->roundRepository->findById($id);

        if ($round === null || !$round->getSeason()?->isActive()) {
            throw new NotFoundHttpException('Manche non trouvée');
        }

        // Redirect to first phase
        $firstPhase = $round->getPhases()->first();
        if ($firstPhase) {
            return new RedirectResponse(
                $this->urlGenerator->generate('app_championship_round_phase', [
                    'id' => $id,
                    'phaseId' => $firstPhase->getId(),
                ])
            );
        }

        // No phases, redirect to maps
        return new RedirectResponse(
            $this->urlGenerator->generate('app_championship_round_maps', ['id' => $id])
        );
    }
}
