<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Championship;

use App\Application\Championship\DTO\RegisterToRoundDTO;
use App\Application\Championship\Exception\AlreadyRegisteredException;
use App\Application\Championship\Exception\RegistrationClosedException;
use App\Application\Championship\Service\RegisterToRoundServiceInterface;
use App\Domain\Championship\Repository\RoundRepositoryInterface;
use App\Domain\Player\Entity\Player;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
#[IsGranted('ROLE_PLAYER')]
final readonly class RegisterController
{
    public function __construct(
        private RoundRepositoryInterface $roundRepository,
        private RegisterToRoundServiceInterface $registerService,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/championship/round/{id}/register', name: 'app_championship_register', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function __invoke(int $id, Request $request, #[CurrentUser] Player $player): Response
    {
        $round = $this->roundRepository->findById($id);

        if ($round === null) {
            throw new NotFoundHttpException('Manche non trouvée');
        }

        /** @var Session $session */
        $session = $request->getSession();

        try {
            $dto = new RegisterToRoundDTO(
                $round,
                $player,
                $request->request->getBoolean('availableSemiFinal1', true),
                $request->request->getBoolean('availableSemiFinal2', true),
                $request->request->getBoolean('availableFinal', true),
            );
            $this->registerService->register($dto);
            $session->getFlashBag()->add('success', 'Inscription réussie !');
        } catch (RegistrationClosedException $e) {
            $session->getFlashBag()->add('error', $e->getMessage());
        } catch (AlreadyRegisteredException $e) {
            $session->getFlashBag()->add('error', $e->getMessage());
        }

        return new RedirectResponse(
            $this->urlGenerator->generate('app_championship_round_show', ['id' => $id])
        );
    }
}
