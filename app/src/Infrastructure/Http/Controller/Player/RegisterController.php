<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Player;

use App\Application\Player\Service\RegisterPlayerServiceInterface;
use App\Infrastructure\Http\Form\RegisterPlayerType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

#[AsController]
final readonly class RegisterController
{
    public function __construct(
        private Environment $twig,
        private FormFactoryInterface $formFactory,
        private RegisterPlayerServiceInterface $registerService,
    ) {
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        $form = $this->formFactory->create(RegisterPlayerType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->registerService->register($form->getData(), $request->getLocale());

            return new Response(
                $this->twig->render('register/success.html.twig')
            );
        }

        return new Response(
            $this->twig->render('register/index.html.twig', [
                'form' => $form->createView(),
            ])
        );
    }
}
