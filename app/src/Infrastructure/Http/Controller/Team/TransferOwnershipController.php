<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Team;

use App\Application\Team\Service\TransferOwnershipServiceInterface;
use App\Domain\Player\Entity\Player;
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
        private TransferOwnershipServiceInterface $transferOwnershipService,
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

        $newCreatorId = $request->request->get('new_creator_id');

        if ($newCreatorId === null) {
            throw new BadRequestHttpException('Aucun joueur sélectionné.');
        }

        try {
            $this->transferOwnershipService->transferOwnership($team, $player, (int) $newCreatorId);
        } catch (\RuntimeException $e) {
            throw new AccessDeniedHttpException($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        return new RedirectResponse($this->urlGenerator->generate('app_profile'));
    }
}
