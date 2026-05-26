<?php

declare(strict_types=1);

namespace App\Application\Championship\Service;

use App\Application\Championship\DTO\RegisterToRoundDTO;
use App\Application\Championship\Exception\AlreadyRegisteredException;
use App\Application\Championship\Exception\RegistrationClosedException;
use App\Application\Championship\Message\UpdateRankingMessage;
use App\Domain\Championship\Entity\RoundRegistration;
use App\Domain\Championship\Repository\MapRecordRepositoryInterface;
use App\Domain\Championship\Repository\RoundRegistrationRepositoryInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class RegisterToRoundService implements RegisterToRoundServiceInterface
{
    public function __construct(
        private RoundRegistrationRepositoryInterface $registrationRepository,
        private MapRecordRepositoryInterface $mapRecordRepository,
        private MessageBusInterface $messageBus,
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

        // Attach orphan records (round_id IS NULL) of this player on this round's
        // maps so they start contributing to the ranking.
        $roundId = $dto->round->getId();

        if ($roundId !== null) {
            $this->mapRecordRepository->attachOrphanRecordsToRound(
                $dto->player->getLogin(),
                $roundId,
            );
        }

        // Recalculate the qualification ranking so the freshly attached records
        // and the new availability flags propagate to the stored snapshot.
        $this->messageBus->dispatch(new UpdateRankingMessage(new \DateTimeImmutable()));

        return $registration;
    }

    public function isAlreadyRegistered(RegisterToRoundDTO $dto): bool
    {
        return $this->registrationRepository->findByRoundAndPlayer($dto->round, $dto->player) instanceof RoundRegistration;
    }
}
