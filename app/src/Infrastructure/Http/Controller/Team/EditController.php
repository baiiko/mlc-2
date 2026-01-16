<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Team;

use App\Application\Team\DTO\UpdateTeamDTO;
use App\Domain\Player\Entity\Player;
use App\Domain\Team\Repository\TeamJoinRequestRepositoryInterface;
use App\Domain\Team\Repository\TeamRepositoryInterface;
use App\Infrastructure\Http\Form\UpdateTeamType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;

#[AsController]
#[IsGranted('ROLE_PLAYER')]
final readonly class EditController
{
    public function __construct(
        private Environment $twig,
        private FormFactoryInterface $formFactory,
        private TeamRepositoryInterface $teamRepository,
        private TeamJoinRequestRepositoryInterface $joinRequestRepository,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/team/edit', name: 'app_team_edit', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, #[CurrentUser] Player $player): Response
    {
        $team = $player->getTeam();

        if ($team === null) {
            return new RedirectResponse($this->urlGenerator->generate('app_profile'));
        }

        if (!$player->isTeamCreator()) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas le créateur de cette équipe.');
        }

        $dto = new UpdateTeamDTO();
        $dto->tag = $team->getTag();
        $dto->fullName = $team->getFullName();

        $form = $this->formFactory->create(UpdateTeamType::class, $dto);
        $form->handleRequest($request);

        $pendingRequests = $this->joinRequestRepository->findPendingByTeam($team);

        if ($form->isSubmitted() && $form->isValid()) {
            $existingTeam = $this->teamRepository->findByTag($dto->tag);
            if ($existingTeam !== null && $existingTeam->getId() !== $team->getId()) {
                return new Response(
                    $this->twig->render('team/edit.html.twig', [
                        'form' => $form->createView(),
                        'team' => $team,
                        'pendingRequests' => $pendingRequests,
                        'error' => 'Une équipe avec ce tag existe déjà.',
                    ])
                );
            }

            $team->setTag($dto->tag);
            $team->setFullName($dto->fullName);
            $this->teamRepository->save($team);

            return new Response(
                $this->twig->render('team/edit.html.twig', [
                    'form' => $form->createView(),
                    'team' => $team,
                    'pendingRequests' => $pendingRequests,
                    'success' => true,
                ])
            );
        }

        return new Response(
            $this->twig->render('team/edit.html.twig', [
                'form' => $form->createView(),
                'team' => $team,
                'pendingRequests' => $pendingRequests,
                'success' => false,
            ])
        );
    }
}
