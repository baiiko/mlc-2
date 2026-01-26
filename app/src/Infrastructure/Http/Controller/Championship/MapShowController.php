<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Championship;

use App\Domain\Championship\Entity\RoundMap;
use App\Domain\Championship\Repository\MapRecordRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
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
    public function __invoke(string $uid): Response
    {
        $map = $this->entityManager->getRepository(RoundMap::class)->findOneBy(['uid' => $uid]);

        if (!$map) {
            throw new NotFoundHttpException('Map not found');
        }

        $rankings = $this->mapRecordRepository->findRankingsByMapUid($uid);

        return new Response(
            $this->twig->render('championship/map/show.html.twig', [
                'map' => $map,
                'rankings' => $rankings,
            ])
        );
    }
}
