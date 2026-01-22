<?php

declare(strict_types=1);

namespace App\Application\Championship\Service;

use App\Domain\Championship\Repository\RoundRegistrationRepositoryInterface;
use App\Domain\Championship\Repository\RoundRepositoryInterface;
use App\Domain\Player\Entity\Player;

final readonly class UpdateAvailabilityService implements UpdateAvailabilityServiceInterface
{
    public function __construct(
        private RoundRepositoryInterface $roundRepository,
        private RoundRegistrationRepositoryInterface $registrationRepository,
    ) {
    }

    public function updateAvailability(
        int $roundId,
        Player $player,
        bool $availableSemiFinal1,
        bool $availableSemiFinal2,
        bool $availableFinal
    ): void {
        $round = $this->roundRepository->findById($roundId);

        if ($round === null) {
            throw new \RuntimeException('Manche non trouvée');
        }

        $registration = $this->registrationRepository->findByRoundAndPlayer($round, $player);

        if ($registration === null) {
            throw new \RuntimeException('Vous n\'êtes pas inscrit à cette manche');
        }

        $registration->setAvailableSemiFinal1($availableSemiFinal1);
        $registration->setAvailableSemiFinal2($availableSemiFinal2);
        $registration->setAvailableFinal($availableFinal);

        $this->registrationRepository->save($registration);
    }
}
