<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Championship;

use App\Domain\Championship\Entity\Round;
use App\Domain\Championship\Entity\RoundMap;
use App\Domain\Championship\Enum\GameMode;
use App\Domain\Championship\Repository\MapRecordRepositoryInterface;
use App\Domain\Player\Entity\Player;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Twig\Environment;

#[AsController]
final readonly class MapShowController
{
    public function __construct(
        private Environment $twig,
        private EntityManagerInterface $entityManager,
        private MapRecordRepositoryInterface $mapRecordRepository,
    ) {
    }

    #[Route('/championship/map/{uid}', name: 'app_championship_map_show', methods: ['GET'])]
    public function __invoke(string $uid, #[CurrentUser] ?Player $player): Response
    {
        $map = $this->entityManager->getRepository(RoundMap::class)->findOneBy(['uid' => $uid]);

        if (!$map) {
            throw new NotFoundHttpException('Map not found');
        }

        // Rankings grouped by round
        $rankingsByRound = $this->mapRecordRepository->findRankingsByMapUidGroupedByRound($uid);

        // Get round names for display
        $roundIds = array_keys($rankingsByRound);
        $rounds = [];
        if (!empty($roundIds)) {
            $roundEntities = $this->entityManager->getRepository(Round::class)->findBy(['id' => $roundIds]);
            foreach ($roundEntities as $round) {
                $rounds[$round->getId()] = $round;
            }
        }

        // Best rankings per game mode (best time per player)
        $rankingsByGameMode = $this->mapRecordRepository->findBestRankingsByMapUidPerGameMode($uid);

        // Best single lap record (all modes combined)
        $bestLapRecord = $this->mapRecordRepository->findBestLapRecord($uid);

        return new Response(
            $this->twig->render('championship/map/show.html.twig', [
                'map' => $map,
                'rankingsByRound' => $rankingsByRound,
                'rounds' => $rounds,
                'rankingsByGameMode' => $rankingsByGameMode,
                'gameModes' => GameMode::cases(),
                'player' => $player,
                'bestLapRecord' => $bestLapRecord,
            ])
        );
    }
}
