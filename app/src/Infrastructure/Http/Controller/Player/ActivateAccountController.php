<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Player;

use App\Application\Player\Service\ActivateAccountServiceInterface;
use App\Domain\Player\Entity\Player;
use App\Infrastructure\Http\Form\ActivateAccountType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

#[AsController]
final readonly class ActivateAccountController
{
    public function __construct(
        private Environment $twig,
        private FormFactoryInterface $formFactory,
        private ActivateAccountServiceInterface $activateAccountService,
    ) {
    }

    #[Route('/activate/{token}', name: 'app_activate_account', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, string $token): Response
    {
        $player = $this->activateAccountService->findByToken($token);

        if (!$player instanceof Player) {
            return new Response(
                $this->twig->render('activate/invalid.html.twig'),
                Response::HTTP_NOT_FOUND
            );
        }

        $form = $this->formFactory->create(ActivateAccountType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->activateAccountService->activate($player, $form->getData()->password);

            return new Response(
                $this->twig->render('activate/success.html.twig', [
                    'player' => $player,
                ])
            );
        }

        return new Response(
            $this->twig->render('activate/index.html.twig', [
                'form' => $form->createView(),
                'player' => $player,
            ])
        );
    }
}
