<?php

declare(strict_types=1);

namespace App\Domain\Championship\Repository;

use App\Domain\Championship\Entity\Round;
use App\Domain\Championship\Entity\RoundRegistration;
use App\Domain\Player\Entity\Player;
use App\Domain\Team\Entity\Team;

interface RoundRegistrationRepositoryInterface
{
    public function save(RoundRegistration $registration): void;

    public function delete(RoundRegistration $registration): void;

    public function findById(int $id): ?RoundRegistration;

    public function findByRoundAndPlayer(Round $round, Player $player): ?RoundRegistration;

    /**
     * @return RoundRegistration[]
     */
    public function findByRound(Round $round): array;

    /**
     * @return RoundRegistration[]
     */
    public function findByRoundAndTeam(Round $round, Team $team): array;

    public function countByRound(Round $round): int;

    public function countByRoundAndTeam(Round $round, Team $team): int;
}
