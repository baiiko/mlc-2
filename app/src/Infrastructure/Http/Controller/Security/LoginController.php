<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Security;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Twig\Environment;

#[AsController]
final readonly class LoginController
{
    public function __construct(
        private Environment $twig,
        private AuthenticationUtils $authenticationUtils,
    ) {
    }

    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(): Response
    {
        $error = $this->authenticationUtils->getLastAuthenticationError();
        $lastUsername = $this->authenticationUtils->getLastUsername();

        return new Response(
            $this->twig->render('security/login.html.twig', [
                'last_username' => $lastUsername,
                'error' => $error,
            ])
        );
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): never
    {
        throw new \LogicException('This method will be intercepted by the logout key on your firewall.');
    }
}
