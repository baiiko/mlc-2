<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Security;

use App\Application\Player\Service\PasswordResetServiceInterface;
use App\Domain\Player\Entity\Player;
use App\Infrastructure\Http\Form\ForgotPasswordType;
use App\Infrastructure\Http\Form\ResetPasswordType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

#[AsController]
final readonly class ForgotPasswordController
{
    public function __construct(
        private Environment $twig,
        private FormFactoryInterface $formFactory,
        private PasswordResetServiceInterface $passwordResetService,
    ) {
    }

    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(Request $request): Response
    {
        $form = $this->formFactory->create(ForgotPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->getData()->email;
            $locale = $request->getLocale();

            $this->passwordResetService->requestPasswordReset($email, $locale);

            // Always show success message to prevent email enumeration
            return new Response(
                $this->twig->render('security/forgot_password_sent.html.twig')
            );
        }

        return new Response(
            $this->twig->render('security/forgot_password.html.twig', [
                'form' => $form->createView(),
            ])
        );
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(Request $request, string $token): Response
    {
        $player = $this->passwordResetService->findByResetToken($token);

        if (!$player instanceof Player) {
            return new Response(
                $this->twig->render('security/reset_password_invalid.html.twig'),
                Response::HTTP_NOT_FOUND
            );
        }

        $form = $this->formFactory->create(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->passwordResetService->resetPassword($player, $form->getData()->password);

            return new Response(
                $this->twig->render('security/reset_password_success.html.twig')
            );
        }

        return new Response(
            $this->twig->render('security/reset_password.html.twig', [
                'form' => $form->createView(),
                'player' => $player,
            ])
        );
    }
}
