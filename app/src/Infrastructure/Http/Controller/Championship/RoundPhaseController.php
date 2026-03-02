<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Championship;

use App\Application\Championship\Service\CalculateRankingServiceInterface;
use App\Application\Championship\Service\RoundRankingServiceInterface;
use App\Domain\Championship\Entity\Phase;
use App\Domain\Championship\Entity\PhaseType;
use App\Domain\Championship\Repository\PhaseRepositoryInterface;
use App\Domain\Championship\Repository\RoundRegistrationRepositoryInterface;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\Repository\PlayerRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Twig\Environment;

#[AsController]
final readonly class RoundPhaseController
{
    public function __construct(
        private Environment $twig,
        private PhaseRepositoryInterface $phaseRepository,
        private RoundRegistrationRepositoryInterface $registrationRepository,
        private CalculateRankingServiceInterface $rankingService,
        private RoundRankingServiceInterface $roundRankingService,
        private PlayerRepositoryInterface $playerRepository,
    ) {
    }

    #[Route('/championship/round/{id}/phase/{phaseId}', name: 'app_championship_round_phase', requirements: ['id' => '\d+', 'phaseId' => '\d+'], methods: ['GET'])]
    public function __invoke(int $id, int $phaseId, #[CurrentUser] ?Player $player): Response
    {
        $phase = $this->phaseRepository->findById($phaseId);

        if (!$phase instanceof Phase || $phase->getRound()?->getId() !== $id) {
            throw new NotFoundHttpException('Phase non trouvée');
        }

        $round = $phase->getRound();

        if (!$round->getSeason()?->isActive()) {
            throw new NotFoundHttpException('Manche non trouvée');
        }

        $registrations = $this->registrationRepository->findByRound($round);
        $playerRegistration = null;

        if ($player instanceof Player) {
            $playerRegistration = $this->registrationRepository->findByRoundAndPlayer($round, $player);
        }

        $phaseRanking = null;
        $qualificationRanking = null;
        $finalRanking = null;
        $phasePlayers = [];

        // Get players for this phase (semi-final, final)
        if ($phase->getPlayers() !== null && $phase->getPlayers() !== []) {
            $phasePlayers = $this->playerRepository->findByLogins($phase->getPlayers());
        }

        if ($phase->isPlayable()) {
            $phaseRanking = [
                'individual' => $this->rankingService->calculatePhaseIndividualRanking($phase),
                'team' => $this->rankingService->calculatePhaseTeamRanking($phase),
            ];

            // Calculate qualification ranking for qualification phase
            if ($phase->getType() === PhaseType::Qualification) {
                // Use stored ranking for finished rounds, recalculate for active ones
                if (!$round->isActive() && $phase->getRanking() !== null) {
                    $qualificationRanking = $phase->getRanking();
                } else {
                    $qualificationRanking = $this->roundRankingService->calculateQualificationRanking($round, $phase);

                    // Store ranking in phase if changed
                    if ($phase->getRanking() !== $qualificationRanking) {
                        $phase->setRanking($qualificationRanking);
                        $phase->setRankingUpdatedAt(new \DateTimeImmutable());
                        $this->phaseRepository->save($phase);
                    }
                }
            }

            // Use stored ranking for final phase on finished rounds
            if ($phase->getType() === PhaseType::Final && !$round->isActive() && $phase->getRanking() !== null) {
                $storedData = $phase->getRanking();

                if (isset($storedData['ranking'])) {
                    $finalRanking = $storedData;
                } else {
                    // Legacy format without maps
                    $finalRanking = ['ranking' => $storedData, 'maps' => []];
                }
            }
        }

        return new Response(
            $this->twig->render('championship/round/phase.html.twig', [
                'round' => $round,
                'season' => $round->getSeason(),
                'phase' => $phase,
                'registrations' => $registrations,
                'playerRegistration' => $playerRegistration,
                'player' => $player,
                'phaseRanking' => $phaseRanking,
                'qualificationRanking' => $qualificationRanking,
                'finalRanking' => $finalRanking,
                'phasePlayers' => $phasePlayers,
            ])
        );
    }
}
