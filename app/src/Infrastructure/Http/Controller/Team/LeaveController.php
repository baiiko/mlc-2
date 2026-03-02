<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Team;

use App\Domain\Player\Entity\Player;
use App\Domain\Team\Entity\TeamMembership;
use App\Domain\Team\Repository\TeamMembershipRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
#[IsGranted('ROLE_PLAYER')]
final readonly class LeaveController
{
    public function __construct(
        private TeamMembershipRepositoryInterface $membershipRepository,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/team/leave', name: 'app_team_leave', methods: ['POST'])]
    public function __invoke(#[CurrentUser] Player $player): RedirectResponse
    {
        $membership = $player->getActiveMembership();

        if (!$membership instanceof TeamMembership) {
            return new RedirectResponse($this->urlGenerator->generate('app_profile'));
        }

        if ($player->isTeamCreator()) {
            throw new AccessDeniedHttpException('Le créateur ne peut pas quitter l\'équipe.');
        }

        $membership->leave();
        $this->membershipRepository->save($membership);

        return new RedirectResponse($this->urlGenerator->generate('app_profile'));
    }
}
