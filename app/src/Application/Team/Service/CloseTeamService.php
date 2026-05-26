<?php

declare(strict_types=1);

namespace App\Application\Team\Service;

use App\Application\Championship\Service\SyncCurrentRegistrationTeamService;
use App\Domain\Player\Entity\Player;
use App\Domain\Team\Entity\Team;
use App\Domain\Team\Repository\TeamMembershipRepositoryInterface;
use App\Domain\Team\Repository\TeamRepositoryInterface;

final readonly class CloseTeamService implements CloseTeamServiceInterface
{
    public function __construct(
        private TeamRepositoryInterface $teamRepository,
        private TeamMembershipRepositoryInterface $membershipRepository,
        private SyncCurrentRegistrationTeamService $registrationTeamSync,
    ) {
    }

    public function closeTeam(Team $team, Player $player): void
    {
        if (!$player->isTeamCreator() || $team->getCreator()->getId() !== $player->getId()) {
            throw new \RuntimeException('Seul le créateur peut clôturer l\'équipe.');
        }

        // Make all members leave
        foreach ($team->getActiveMemberships() as $membership) {
            $member = $membership->getPlayer();
            $membership->leave();
            $this->membershipRepository->save($membership);
            $this->registrationTeamSync->syncForPlayer($member, null);
        }

        // Soft delete team
        $this->teamRepository->delete($team);
    }
}
