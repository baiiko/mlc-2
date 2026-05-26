<?php

declare(strict_types=1);

namespace App\Application\Championship\Service;

use App\Domain\Championship\Entity\Round;
use App\Domain\Championship\Entity\RoundRegistration;
use App\Domain\Championship\Repository\RoundRegistrationRepositoryInterface;
use App\Domain\Championship\Repository\RoundRepositoryInterface;
use App\Domain\Player\Entity\Player;
use App\Domain\Team\Entity\Team;

/**
 * Keeps the current round's RoundRegistration.team snapshot in sync
 * whenever a player's team membership changes (join, leave, kick, team
 * closure). Has no effect outside of an ongoing/upcoming round.
 */
final readonly class SyncCurrentRegistrationTeamService
{
    public function __construct(
        private RoundRepositoryInterface $roundRepository,
        private RoundRegistrationRepositoryInterface $registrationRepository,
    ) {
    }

    public function syncForPlayer(Player $player, ?Team $team): void
    {
        $currentRound = $this->roundRepository->findCurrentOrUpcoming();

        if (!$currentRound instanceof Round) {
            return;
        }

        $registration = $this->registrationRepository->findByRoundAndPlayer($currentRound, $player);

        if (!$registration instanceof RoundRegistration) {
            return;
        }

        $registration->setTeam($team);
        $this->registrationRepository->save($registration);
    }
}
