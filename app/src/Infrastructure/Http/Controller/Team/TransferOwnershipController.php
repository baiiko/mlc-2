<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Team;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Repository\PlayerRepositoryInterface;
use App\Domain\Team\Repository\TeamRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
#[IsGranted('ROLE_PLAYER')]
final readonly class TransferOwnershipController
{
    public function __construct(
        private TeamRepositoryInterface $teamRepository,
        private PlayerRepositoryInterface $playerRepository,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/team/transfer', name: 'app_team_transfer', methods: ['POST'])]
    public function __invoke(Request $request, #[CurrentUser] Player $player): Response
    {
        $team = $player->getTeam();

        if ($team === null) {
            return new RedirectResponse($this->urlGenerator->generate('app_profile'));
        }

        if (!$player->isTeamCreator()) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas le créateur de cette équipe.');
        }

        $newCreatorId = $request->request->get('new_creator_id');

        if ($newCreatorId === null) {
            throw new BadRequestHttpException('Aucun joueur sélectionné.');
        }

        $newCreator = $this->playerRepository->findById((int) $newCreatorId);

        if ($newCreator === null || $newCreator->getTeam()?->getId() !== $team->getId()) {
            throw new BadRequestHttpException('Le joueur sélectionné n\'est pas membre de cette équipe.');
        }

        if ($newCreator->getId() === $player->getId()) {
            return new RedirectResponse($this->urlGenerator->generate('app_team_edit'));
        }

        $team->setCreator($newCreator);
        $this->teamRepository->save($team);

        return new RedirectResponse($this->urlGenerator->generate('app_profile'));
    }
}
