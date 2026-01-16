<?php

declare(strict_types=1);

namespace App\Application\Team\Service;

use App\Application\Team\DTO\CreateTeamDTO;
use App\Domain\Player\Entity\Player;
use App\Domain\Team\Entity\Team;
use App\Domain\Team\Entity\TeamMembership;
use App\Domain\Team\Repository\TeamMembershipRepositoryInterface;
use App\Domain\Team\Repository\TeamRepositoryInterface;

final readonly class CreateTeamService implements CreateTeamServiceInterface
{
    public function __construct(
        private TeamRepositoryInterface $teamRepository,
        private TeamMembershipRepositoryInterface $membershipRepository,
    ) {
    }

    public function create(CreateTeamDTO $dto, Player $creator): Team
    {
        $team = new Team($dto->tag, $dto->fullName, $creator);

        $this->teamRepository->save($team);

        $membership = new TeamMembership($creator, $team);
        $this->membershipRepository->save($membership);

        return $team;
    }
}
