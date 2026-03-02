<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Team;

use App\Application\Team\Service\HandleJoinRequestServiceInterface;
use App\Domain\Player\Entity\Player;
use App\Domain\Team\Repository\TeamJoinRequestRepositoryInterface;
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
        private HandleJoinRequestServiceInterface $handleJoinRequestService,
        private TeamJoinRequestRepositoryInterface $joinRequestRepository,
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
    public function accept(int $id, #[CurrentUser] Player $player): RedirectResponse
    {
        try {
            $this->handleJoinRequestService->acceptRequest($id, $player);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'Demande introuvable.') {
                throw new NotFoundHttpException($e->getMessage(), $e);
            }

            throw new AccessDeniedHttpException($e->getMessage(), $e);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_team_requests'));
    }

    #[Route('/team/requests/{id}/reject', name: 'app_team_request_reject', methods: ['POST'])]
    public function reject(int $id, #[CurrentUser] Player $player): RedirectResponse
    {
        try {
            $this->handleJoinRequestService->rejectRequest($id, $player);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'Demande introuvable.') {
                throw new NotFoundHttpException($e->getMessage(), $e);
            }

            throw new AccessDeniedHttpException($e->getMessage(), $e);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_team_requests'));
    }
}
