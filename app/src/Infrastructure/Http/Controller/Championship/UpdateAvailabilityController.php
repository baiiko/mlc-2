<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Championship;

use App\Domain\Championship\Repository\RoundRegistrationRepositoryInterface;
use App\Domain\Championship\Repository\RoundRepositoryInterface;
use App\Domain\Player\Entity\Player;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
#[IsGranted('ROLE_PLAYER')]
final readonly class UpdateAvailabilityController
{
    public function __construct(
        private RoundRepositoryInterface $roundRepository,
        private RoundRegistrationRepositoryInterface $registrationRepository,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/championship/round/{id}/availability', name: 'app_championship_update_availability', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function __invoke(int $id, Request $request, #[CurrentUser] Player $player): Response
    {
        $round = $this->roundRepository->findById($id);

        if ($round === null) {
            throw new NotFoundHttpException('Manche non trouvée');
        }

        $registration = $this->registrationRepository->findByRoundAndPlayer($round, $player);

        if ($registration === null) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas inscrit à cette manche');
        }

        // Update availability
        $registration->setAvailableSemiFinal1($request->request->getBoolean('availableSemiFinal1', false));
        $registration->setAvailableSemiFinal2($request->request->getBoolean('availableSemiFinal2', false));
        $registration->setAvailableFinal($request->request->getBoolean('availableFinal', false));

        $this->registrationRepository->save($registration);

        /** @var Session $session */
        $session = $request->getSession();
        $session->getFlashBag()->add('success', 'Disponibilités mises à jour !');

        return new RedirectResponse(
            $this->urlGenerator->generate('app_championship_round_show', ['id' => $id])
        );
    }
}
