<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Championship;

use App\Domain\Championship\Repository\SeasonRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

#[AsController]
final readonly class SeasonListController
{
    public function __construct(
        private Environment $twig,
        private SeasonRepositoryInterface $seasonRepository,
    ) {
    }

    #[Route('/championship', name: 'app_championship_seasons', methods: ['GET'])]
    public function __invoke(): Response
    {
        $seasons = $this->seasonRepository->findAllActive();

        return new Response(
            $this->twig->render('championship/season/list.html.twig', [
                'seasons' => $seasons,
            ])
        );
    }
}
