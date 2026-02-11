<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Championship;

use App\Domain\Championship\Entity\PhaseType;
use App\Domain\Championship\Repository\MapRecordRepositoryInterface;
use App\Domain\Championship\Repository\RoundRegistrationRepositoryInterface;
use App\Domain\Championship\Repository\RoundRepositoryInterface;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\Repository\PlayerRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Twig\Environment;

#[AsController]
final readonly class RoundPlayerController
{
    public function __construct(
        private Environment $twig,
        private RoundRepositoryInterface $roundRepository,
        private PlayerRepositoryInterface $playerRepository,
        private RoundRegistrationRepositoryInterface $registrationRepository,
        private MapRecordRepositoryInterface $mapRecordRepository,
    ) {
    }

    #[Route('/championship/round/{id}/player/{login}', name: 'app_championship_round_player', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function __invoke(int $id, string $login, #[CurrentUser] ?Player $currentPlayer): Response
    {
        $round = $this->roundRepository->findById($id);

        if ($round === null || !$round->getSeason()?->isActive()) {
            throw new NotFoundHttpException('Manche non trouvée');
        }

        $targetPlayer = $this->playerRepository->findByLogin($login);
        if ($targetPlayer === null) {
            throw new NotFoundHttpException('Joueur non trouvé');
        }

        $registration = $this->registrationRepository->findByRoundAndPlayer($round, $targetPlayer);
        $registrations = $this->registrationRepository->findByRound($round);

        // Get player's ranking data from stored qualification phase ranking
        $playerRankingData = null;
        foreach ($round->getPhases() as $phase) {
            if ($phase->getType() === PhaseType::Qualification) {
                $playerRankingData = $phase->getPlayerRanking($login);
                break;
            }
        }

        // Get all records for all maps, grouped by map
        $mapRecords = [];
        $playerRecords = [];
        foreach ($round->getMaps() as $map) {
            if ($map->getUid()) {
                $allRecords = $this->mapRecordRepository->findByMapUidWithPlayer($map->getUid(), $round->getId());
                $mapRecords[$map->getUid()] = $allRecords;

                // Find player's records and their ranking
                $playerMapRecords = [];
                foreach ($allRecords as $index => $recordData) {
                    if ($recordData['record']->getPlayerLogin() === $login) {
                        // Find first place time for this laps count
                        $laps = $recordData['record']->getLaps();
                        $firstPlaceTime = null;
                        $position = 1;
                        $positionForLaps = 1;

                        foreach ($allRecords as $r) {
                            if ($r['record']->getLaps() === $laps) {
                                if ($firstPlaceTime === null) {
                                    $firstPlaceTime = $r['record']->getTime();
                                }
                                if ($r['record']->getPlayerLogin() === $login) {
                                    break;
                                }
                                $positionForLaps++;
                            }
                        }

                        $diff = $recordData['record']->getTime() - $firstPlaceTime;
                        $playerMapRecords[$laps] = [
                            'record' => $recordData['record'],
                            'playerPseudo' => $recordData['playerPseudo'],
                            'position' => $positionForLaps,
                            'diff' => $diff,
                        ];
                    }
                }
                $playerRecords[$map->getUid()] = $playerMapRecords;
            }
        }

        return new Response(
            $this->twig->render('championship/round/player.html.twig', [
                'round' => $round,
                'season' => $round->getSeason(),
                'registrations' => $registrations,
                'targetPlayer' => $targetPlayer,
                'registration' => $registration,
                'playerRankingData' => $playerRankingData,
                'playerRecords' => $playerRecords,
                'player' => $currentPlayer,
            ])
        );
    }
}
