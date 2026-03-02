<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Player;

use App\Application\Player\Service\ChangePasswordServiceInterface;
use App\Domain\Player\Entity\Player;
use App\Infrastructure\Http\Form\ChangePasswordType;
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
final readonly class ChangePasswordController
{
    public function __construct(
        private Environment $twig,
        private FormFactoryInterface $formFactory,
        private ChangePasswordServiceInterface $changePasswordService,
    ) {
    }

    #[Route('/profile/password', name: 'app_change_password', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, #[CurrentUser] Player $player): Response
    {
        $form = $this->formFactory->create(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dto = $form->getData();

            $result = $this->changePasswordService->changePassword(
                $player,
                $dto->currentPassword,
                $dto->newPassword
            );

            if (!$result['success']) {
                $form->get('currentPassword')->addError(new FormError($result['error']));

                return new Response(
                    $this->twig->render('profile/change_password.html.twig', [
                        'form' => $form->createView(),
                        'success' => false,
                    ])
                );
            }

            return new Response(
                $this->twig->render('profile/change_password.html.twig', [
                    'form' => $form->createView(),
                    'success' => true,
                ])
            );
        }

        return new Response(
            $this->twig->render('profile/change_password.html.twig', [
                'form' => $form->createView(),
                'success' => false,
            ])
        );
    }
}
