<?php

declare(strict_types=1);

namespace App\Application\Team\Service;

use App\Domain\Player\Entity\Player;
use App\Domain\Team\Entity\Team;
use App\Domain\Team\Repository\TeamMembershipRepositoryInterface;
use App\Domain\Team\Repository\TeamRepositoryInterface;

final readonly class CloseTeamService implements CloseTeamServiceInterface
{
    public function __construct(
        private TeamRepositoryInterface $teamRepository,
        private TeamMembershipRepositoryInterface $membershipRepository,
    ) {
    }

    public function closeTeam(Team $team, Player $player): void
    {
        if (!$player->isTeamCreator() || $team->getCreator()?->getId() !== $player->getId()) {
            throw new \RuntimeException('Seul le créateur peut clôturer l\'équipe.');
        }

        // Make all members leave
        foreach ($team->getActiveMemberships() as $membership) {
            $membership->leave();
            $this->membershipRepository->save($membership);
        }

        // Soft delete team
        $this->teamRepository->delete($team);
    }
}
