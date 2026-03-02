<?php

declare(strict_types=1);

namespace App\Application\Championship\Service;

use App\Application\Championship\DTO\RegisterToRoundDTO;
use App\Application\Championship\Exception\AlreadyRegisteredException;
use App\Application\Championship\Exception\RegistrationClosedException;
use App\Domain\Championship\Entity\RoundRegistration;
use App\Domain\Championship\Repository\RoundRegistrationRepositoryInterface;

final readonly class RegisterToRoundService implements RegisterToRoundServiceInterface
{
    public function __construct(
        private RoundRegistrationRepositoryInterface $registrationRepository,
    ) {
    }

    public function register(RegisterToRoundDTO $dto): RoundRegistration
    {
        if (!$dto->round->isRegistrationOpen()) {
            throw new RegistrationClosedException();
        }

        if ($this->isAlreadyRegistered($dto)) {
            throw new AlreadyRegisteredException();
        }

        $registration = new RoundRegistration(
            $dto->round,
            $dto->player,
            $dto->player->getTeam(),
            $dto->availableSemiFinal1,
            $dto->availableSemiFinal2,
            $dto->availableFinal,
        );

        $this->registrationRepository->save($registration);

        return $registration;
    }

    public function isAlreadyRegistered(RegisterToRoundDTO $dto): bool
    {
        return $this->registrationRepository->findByRoundAndPlayer($dto->round, $dto->player) !== null;
    }
}
