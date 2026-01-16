<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Player;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Repository\PlayerRepositoryInterface;
use App\Domain\Team\Repository\TeamMembershipRepositoryInterface;
use App\Domain\Team\Repository\TeamJoinRequestRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\SecurityBundle\Security;

#[AsController]
#[IsGranted('ROLE_PLAYER')]
final readonly class CloseAccountController
{
    public function __construct(
        private PlayerRepositoryInterface $playerRepository,
        private TeamMembershipRepositoryInterface $membershipRepository,
        private TeamJoinRequestRepositoryInterface $joinRequestRepository,
        private UrlGeneratorInterface $urlGenerator,
        private Security $security,
    ) {
    }

    #[Route('/profile/close-account', name: 'app_close_account', methods: ['POST'])]
    public function __invoke(#[CurrentUser] Player $player): Response
    {
        // Vérifier si le joueur est créateur d'une équipe
        if ($player->isTeamCreator()) {
            throw new AccessDeniedHttpException(
                'Vous devez transférer la propriété ou clôturer votre équipe avant de fermer votre compte.'
            );
        }

        // Annuler les demandes d'adhésion en attente
        $pendingRequest = $this->joinRequestRepository->findPendingByPlayer($player);
        if ($pendingRequest !== null) {
            $this->joinRequestRepository->delete($pendingRequest);
        }

        // Quitter l'équipe si membre
        $membership = $player->getActiveMembership();
        if ($membership !== null) {
            $membership->leave();
            $this->membershipRepository->save($membership);
        }

        // Déconnecter l'utilisateur
        $this->security->logout(false);

        // Supprimer le compte (soft delete grâce à Gedmo)
        $this->playerRepository->delete($player);

        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }
}
