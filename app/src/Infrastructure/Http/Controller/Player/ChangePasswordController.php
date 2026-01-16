<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Player;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Repository\PlayerRepositoryInterface;
use App\Infrastructure\Http\Form\ChangePasswordType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;

#[AsController]
#[IsGranted('ROLE_PLAYER')]
final readonly class ChangePasswordController
{
    public function __construct(
        private Environment $twig,
        private FormFactoryInterface $formFactory,
        private PlayerRepositoryInterface $playerRepository,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[Route('/profile/password', name: 'app_change_password', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, #[CurrentUser] Player $player): Response
    {
        $form = $this->formFactory->create(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dto = $form->getData();

            // Verify current password
            if (!$this->passwordHasher->isPasswordValid($player, $dto->currentPassword)) {
                $form->get('currentPassword')->addError(new FormError('Le mot de passe actuel est incorrect.'));
            }

            if ($form->isValid()) {
                $hashedPassword = $this->passwordHasher->hashPassword($player, $dto->newPassword);
                $player->setPassword($hashedPassword);
                $this->playerRepository->save($player);

                return new Response(
                    $this->twig->render('profile/change_password.html.twig', [
                        'form' => $form->createView(),
                        'success' => true,
                    ])
                );
            }
        }

        return new Response(
            $this->twig->render('profile/change_password.html.twig', [
                'form' => $form->createView(),
                'success' => false,
            ])
        );
    }
}
