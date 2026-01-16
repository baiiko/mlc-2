<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Player;

use App\Application\Player\DTO\UpdateProfileDTO;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\Repository\PlayerRepositoryInterface;
use App\Domain\Team\Repository\TeamJoinRequestRepositoryInterface;
use App\Infrastructure\Http\Form\UpdateProfileType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;

#[AsController]
#[IsGranted('ROLE_PLAYER')]
final readonly class ProfileController
{
    public function __construct(
        private Environment $twig,
        private FormFactoryInterface $formFactory,
        private PlayerRepositoryInterface $playerRepository,
        private TeamJoinRequestRepositoryInterface $joinRequestRepository,
    ) {
    }

    #[Route('/profile', name: 'app_profile', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, #[CurrentUser] Player $player): Response
    {
        $dto = new UpdateProfileDTO();
        $dto->pseudo = $player->getPseudo();
        $dto->email = $player->getEmail();
        $dto->discord = $player->getDiscord();
        $dto->newsletter = $player->hasNewsletter();

        $form = $this->formFactory->create(UpdateProfileType::class, $dto);
        $form->handleRequest($request);

        $pendingRequest = $this->joinRequestRepository->findPendingByPlayer($player);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check email uniqueness (excluding current player)
            if ($dto->email !== $player->getEmail()) {
                $existingPlayer = $this->playerRepository->findByEmail($dto->email);
                if ($existingPlayer !== null) {
                    $form->get('email')->addError(new FormError('Cette adresse email est déjà utilisée.'));
                }
            }

            if ($form->isValid()) {
                $player->setPseudo($dto->pseudo);
                $player->setEmail($dto->email);
                $player->setDiscord($dto->discord);
                $player->setNewsletter($dto->newsletter);
                $this->playerRepository->save($player);

                return new Response(
                    $this->twig->render('profile/index.html.twig', [
                        'form' => $form->createView(),
                        'player' => $player,
                        'pendingRequest' => $pendingRequest,
                        'success' => true,
                    ])
                );
            }
        }

        return new Response(
            $this->twig->render('profile/index.html.twig', [
                'form' => $form->createView(),
                'player' => $player,
                'pendingRequest' => $pendingRequest,
                'success' => false,
            ])
        );
    }
}
