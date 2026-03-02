<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Team;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Repository\PlayerRepositoryInterface;
use App\Domain\Team\Entity\TeamMembership;
use App\Domain\Team\Repository\TeamMembershipRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
#[IsGranted('ROLE_PLAYER')]
final readonly class RemoveMemberController
{
    public function __construct(
        private PlayerRepositoryInterface $playerRepository,
        private TeamMembershipRepositoryInterface $membershipRepository,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/team/member/{playerId}/remove', name: 'app_team_remove_member', methods: ['POST'])]
    public function __invoke(int $playerId, #[CurrentUser] Player $currentPlayer): RedirectResponse
    {
        if (!$currentPlayer->isTeamCreator()) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas le créateur de cette équipe.');
        }

        $team = $currentPlayer->getTeam();

        $player = $this->playerRepository->findById($playerId);

        if (!$player instanceof Player) {
            throw new NotFoundHttpException('Joueur introuvable.');
        }

        $membership = $player->getActiveMembership();

        if (!$membership instanceof TeamMembership || $membership->getTeam()->getId() !== $team->getId()) {
            throw new BadRequestHttpException('Ce joueur n\'est pas membre de votre équipe.');
        }

        if ($team->isCreator($player)) {
            throw new BadRequestHttpException('Impossible de retirer le créateur de l\'équipe.');
        }

        $membership->leave();
        $this->membershipRepository->save($membership);

        return new RedirectResponse($this->urlGenerator->generate('app_team_edit'));
    }
}
