<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Team;

use App\Domain\Player\Entity\Player;
use App\Domain\Team\Repository\TeamMembershipRepositoryInterface;
use App\Domain\Team\Repository\TeamRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
#[IsGranted('ROLE_PLAYER')]
final readonly class CloseController
{
    public function __construct(
        private TeamRepositoryInterface $teamRepository,
        private TeamMembershipRepositoryInterface $membershipRepository,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/team/close', name: 'app_team_close', methods: ['POST'])]
    public function __invoke(#[CurrentUser] Player $player): Response
    {
        $team = $player->getTeam();

        if ($team === null) {
            return new RedirectResponse($this->urlGenerator->generate('app_profile'));
        }

        if (!$player->isTeamCreator()) {
            throw new AccessDeniedHttpException('Seul le créateur peut clôturer l\'équipe.');
        }

        // Faire quitter tous les membres
        foreach ($team->getActiveMemberships() as $membership) {
            $membership->leave();
            $this->membershipRepository->save($membership);
        }

        // Soft delete l'équipe
        $this->teamRepository->delete($team);

        return new RedirectResponse($this->urlGenerator->generate('app_profile'));
    }
}
