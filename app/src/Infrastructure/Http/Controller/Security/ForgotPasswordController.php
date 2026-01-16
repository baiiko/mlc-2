<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Security;

use App\Application\Player\Notification\PlayerNotificationInterface;
use App\Domain\Player\Repository\PlayerRepositoryInterface;
use App\Infrastructure\Http\Form\ForgotPasswordType;
use App\Infrastructure\Http\Form\ResetPasswordType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

#[AsController]
final readonly class ForgotPasswordController
{
    public function __construct(
        private Environment $twig,
        private FormFactoryInterface $formFactory,
        private PlayerRepositoryInterface $playerRepository,
        private PlayerNotificationInterface $playerNotification,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(Request $request): Response
    {
        $form = $this->formFactory->create(ForgotPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->getData()->email;
            $player = $this->playerRepository->findByEmail($email);

            if ($player !== null) {
                $locale = $request->getLocale();
                if (!$player->isActive()) {
                    // Account not activated yet, resend welcome email
                    $player->generateActivationToken();
                    $this->playerRepository->save($player);
                    $this->playerNotification->sendWelcomeEmail($player, $locale);
                } else {
                    // Account active, send password reset email
                    $player->generateResetPasswordToken();
                    $this->playerRepository->save($player);
                    $this->playerNotification->sendPasswordResetEmail($player, $locale);
                }
            }

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
        $player = $this->playerRepository->findByResetPasswordToken($token);

        if ($player === null || !$player->isResetPasswordTokenValid()) {
            return new Response(
                $this->twig->render('security/reset_password_invalid.html.twig'),
                Response::HTTP_NOT_FOUND
            );
        }

        $form = $this->formFactory->create(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hashedPassword = $this->passwordHasher->hashPassword(
                $player,
                $form->getData()->password
            );
            $player->setPassword($hashedPassword);
            $player->clearResetPasswordToken();
            $this->playerRepository->save($player);

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
