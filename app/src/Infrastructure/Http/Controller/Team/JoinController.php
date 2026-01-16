<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Team;

use App\Domain\Player\Entity\Player;
use App\Domain\Team\Entity\TeamJoinRequest;
use App\Domain\Team\Repository\TeamJoinRequestRepositoryInterface;
use App\Domain\Team\Repository\TeamRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;

#[AsController]
#[IsGranted('ROLE_PLAYER')]
final readonly class JoinController
{
    public function __construct(
        private Environment $twig,
        private TeamRepositoryInterface $teamRepository,
        private TeamJoinRequestRepositoryInterface $joinRequestRepository,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/team/join', name: 'app_team_join', methods: ['GET'])]
    public function list(#[CurrentUser] Player $player): Response
    {
        if ($player->hasTeam()) {
            return new RedirectResponse($this->urlGenerator->generate('app_profile'));
        }

        $teams = $this->teamRepository->findAll();
        $pendingRequest = $this->joinRequestRepository->findPendingByPlayer($player);

        return new Response(
            $this->twig->render('team/join.html.twig', [
                'teams' => $teams,
                'pendingRequest' => $pendingRequest,
            ])
        );
    }

    #[Route('/team/join/{id}', name: 'app_team_join_submit', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function requestJoin(int $id, #[CurrentUser] Player $player): Response
    {
        if ($player->hasTeam()) {
            return new RedirectResponse($this->urlGenerator->generate('app_profile'));
        }

        $team = $this->teamRepository->findById($id);

        if ($team === null) {
            throw new BadRequestHttpException('Équipe introuvable.');
        }

        // Check if player already has a pending request (only one allowed at a time)
        $existingRequest = $this->joinRequestRepository->findPendingByPlayer($player);
        if ($existingRequest !== null) {
            return new RedirectResponse($this->urlGenerator->generate('app_team_join'));
        }

        $request = new TeamJoinRequest($player, $team);
        $this->joinRequestRepository->save($request);

        return new RedirectResponse($this->urlGenerator->generate('app_team_join'));
    }

    #[Route('/team/join/cancel', name: 'app_team_join_cancel', methods: ['POST'])]
    public function cancelRequest(#[CurrentUser] Player $player): Response
    {
        $pendingRequest = $this->joinRequestRepository->findPendingByPlayer($player);

        if ($pendingRequest !== null) {
            $this->joinRequestRepository->delete($pendingRequest);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_team_join'));
    }
}
