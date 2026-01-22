<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Player;

use App\Application\Player\Service\CloseAccountServiceInterface;
use App\Domain\Player\Entity\Player;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\SecurityBundle\Security;

#[AsController]
#[IsGranted('ROLE_PLAYER')]
final readonly class CloseAccountController
{
    public function __construct(
        private CloseAccountServiceInterface $closeAccountService,
        private UrlGeneratorInterface $urlGenerator,
        private Security $security,
    ) {
    }

    #[Route('/profile/close-account', name: 'app_close_account', methods: ['POST'])]
    public function __invoke(#[CurrentUser] Player $player): Response
    {
        try {
            $this->closeAccountService->closeAccount($player);
        } catch (\RuntimeException $e) {
            throw new AccessDeniedHttpException($e->getMessage());
        }

        $this->security->logout(false);

        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }
}
