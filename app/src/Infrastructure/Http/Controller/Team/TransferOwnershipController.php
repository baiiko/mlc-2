<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Team;

use App\Application\Team\Service\TransferOwnershipServiceInterface;
use App\Domain\Player\Entity\Player;
use App\Domain\Team\Entity\Team;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
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
        private TransferOwnershipServiceInterface $transferOwnershipService,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/team/transfer', name: 'app_team_transfer', methods: ['POST'])]
    public function __invoke(Request $request, #[CurrentUser] Player $player): RedirectResponse
    {
        $team = $player->getTeam();

        if (!$team instanceof Team) {
            return new RedirectResponse($this->urlGenerator->generate('app_profile'));
        }

        $newCreatorId = $request->request->get('new_creator_id');

        if ($newCreatorId === null) {
            throw new BadRequestHttpException('Aucun joueur sélectionné.');
        }

        try {
            $this->transferOwnershipService->transferOwnership($team, $player, (int) $newCreatorId);
        } catch (\RuntimeException $e) {
            throw new AccessDeniedHttpException($e->getMessage(), $e);
        } catch (\InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_profile'));
    }
}
