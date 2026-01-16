<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Team;

use App\Domain\Player\Entity\Player;
use App\Domain\Team\Entity\TeamMembership;
use App\Domain\Team\Repository\TeamJoinRequestRepositoryInterface;
use App\Domain\Team\Repository\TeamMembershipRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;

#[AsController]
#[IsGranted('ROLE_PLAYER')]
final readonly class RequestsController
{
    public function __construct(
        private Environment $twig,
        private TeamJoinRequestRepositoryInterface $joinRequestRepository,
        private TeamMembershipRepositoryInterface $membershipRepository,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/team/requests', name: 'app_team_requests', methods: ['GET'])]
    public function list(#[CurrentUser] Player $player): Response
    {
        if (!$player->isTeamCreator()) {
            return new RedirectResponse($this->urlGenerator->generate('app_profile'));
        }

        $team = $player->getTeam();

        $requests = $this->joinRequestRepository->findPendingByTeam($team);

        return new Response(
            $this->twig->render('team/requests.html.twig', [
                'requests' => $requests,
                'team' => $team,
            ])
        );
    }

    #[Route('/team/requests/{id}/accept', name: 'app_team_request_accept', methods: ['POST'])]
    public function accept(int $id, #[CurrentUser] Player $player): Response
    {
        $request = $this->joinRequestRepository->findById($id);

        if ($request === null) {
            throw new NotFoundHttpException('Demande introuvable.');
        }

        $team = $player->getTeam();

        if (!$player->isTeamCreator() || $request->getTeam()->getId() !== $player->getTeam()->getId()) {
            throw new AccessDeniedHttpException('Accès refusé.');
        }

        $requestPlayer = $request->getPlayer();

        // Check if player doesn't already have a team
        if ($requestPlayer->hasTeam()) {
            $this->joinRequestRepository->delete($request);
            return new RedirectResponse($this->urlGenerator->generate('app_team_requests'));
        }

        $membership = new TeamMembership($requestPlayer, $team);
        $this->membershipRepository->save($membership);

        $request->accept();
        $this->joinRequestRepository->save($request);

        return new RedirectResponse($this->urlGenerator->generate('app_team_requests'));
    }

    #[Route('/team/requests/{id}/reject', name: 'app_team_request_reject', methods: ['POST'])]
    public function reject(int $id, #[CurrentUser] Player $player): Response
    {
        $request = $this->joinRequestRepository->findById($id);

        if ($request === null) {
            throw new NotFoundHttpException('Demande introuvable.');
        }

        if (!$player->isTeamCreator() || $request->getTeam()->getId() !== $player->getTeam()->getId()) {
            throw new AccessDeniedHttpException('Accès refusé.');
        }

        $request->reject();
        $this->joinRequestRepository->save($request);

        return new RedirectResponse($this->urlGenerator->generate('app_team_requests'));
    }
}
