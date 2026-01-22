<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Team;

use App\Application\Team\Service\CloseTeamServiceInterface;
use App\Domain\Player\Entity\Player;
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
        private CloseTeamServiceInterface $closeTeamService,
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

        try {
            $this->closeTeamService->closeTeam($team, $player);
        } catch (\RuntimeException $e) {
            throw new AccessDeniedHttpException($e->getMessage());
        }

        return new RedirectResponse($this->urlGenerator->generate('app_profile'));
    }
}
