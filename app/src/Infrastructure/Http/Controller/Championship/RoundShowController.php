<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Championship;

use App\Application\Championship\Service\RoundDataServiceInterface;
use App\Domain\Player\Entity\Player;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Twig\Environment;

#[AsController]
final readonly class RoundShowController
{
    public function __construct(
        private Environment $twig,
        private RoundDataServiceInterface $roundDataService,
    ) {
    }

    #[Route('/championship/round/{id}', name: 'app_championship_round_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function __invoke(int $id, #[CurrentUser] ?Player $player): Response
    {
        try {
            $data = $this->roundDataService->getRoundData($id, $player);
        } catch (\RuntimeException $e) {
            throw new NotFoundHttpException($e->getMessage());
        }

        return new Response(
            $this->twig->render('championship/round/show.html.twig', [
                'round' => $data['round'],
                'season' => $data['round']->getSeason(),
                'registrations' => $data['registrations'],
                'playerRegistration' => $data['playerRegistration'],
                'player' => $player,
                'phaseRankings' => $data['phaseRankings'],
                'mapRecords' => $data['mapRecords'],
            ])
        );
    }
}
