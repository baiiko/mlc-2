<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Championship;

use App\Domain\Championship\Entity\Round;
use App\Domain\Championship\Repository\MapRecordRepositoryInterface;
use App\Domain\Championship\Repository\RoundRegistrationRepositoryInterface;
use App\Domain\Championship\Repository\RoundRepositoryInterface;
use App\Domain\Player\Entity\Player;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Twig\Environment;

#[AsController]
final readonly class RoundMapsController
{
    public function __construct(
        private Environment $twig,
        private RoundRepositoryInterface $roundRepository,
        private RoundRegistrationRepositoryInterface $registrationRepository,
        private MapRecordRepositoryInterface $mapRecordRepository,
    ) {
    }

    #[Route('/championship/round/{id}/maps', name: 'app_championship_round_maps', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function __invoke(int $id, #[CurrentUser] ?Player $player): Response
    {
        $round = $this->roundRepository->findById($id);

        if (!$round instanceof Round || !$round->getSeason()?->isActive()) {
            throw new NotFoundHttpException('Manche non trouvée');
        }

        $registrations = $this->registrationRepository->findByRound($round);

        // Get records for each map
        $mapRecords = [];

        foreach ($round->getMaps() as $map) {
            if ($map->getUid()) {
                $mapRecords[$map->getUid()] = $this->mapRecordRepository->findByMapUidWithPlayer($map->getUid(), $round->getId());
            }
        }

        return new Response(
            $this->twig->render('championship/round/maps.html.twig', [
                'round' => $round,
                'season' => $round->getSeason(),
                'registrations' => $registrations,
                'mapRecords' => $mapRecords,
                'player' => $player,
            ])
        );
    }
}
