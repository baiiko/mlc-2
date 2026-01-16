<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Team;

use App\Application\Team\Service\CreateTeamServiceInterface;
use App\Domain\Player\Entity\Player;
use App\Infrastructure\Http\Form\CreateTeamType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;

#[AsController]
#[IsGranted('ROLE_PLAYER')]
final readonly class CreateController
{
    public function __construct(
        private Environment $twig,
        private FormFactoryInterface $formFactory,
        private CreateTeamServiceInterface $createTeamService,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/team/create', name: 'app_team_create', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, #[CurrentUser] Player $player): Response
    {
        if ($player->hasTeam()) {
            return new RedirectResponse($this->urlGenerator->generate('app_profile'));
        }

        $form = $this->formFactory->create(CreateTeamType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $team = $this->createTeamService->create($form->getData(), $player);

            return new Response(
                $this->twig->render('team/success.html.twig', [
                    'team' => $team,
                ])
            );
        }

        return new Response(
            $this->twig->render('team/create.html.twig', [
                'form' => $form->createView(),
            ])
        );
    }
}
