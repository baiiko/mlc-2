<?php

declare(strict_types=1);

namespace App\Application\Team\Service;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Repository\PlayerRepositoryInterface;
use App\Domain\Team\Entity\Team;
use App\Domain\Team\Repository\TeamRepositoryInterface;

final readonly class TransferOwnershipService implements TransferOwnershipServiceInterface
{
    public function __construct(
        private TeamRepositoryInterface $teamRepository,
        private PlayerRepositoryInterface $playerRepository,
    ) {
    }

    public function transferOwnership(Team $team, Player $currentCreator, int $newCreatorId): void
    {
        if (!$currentCreator->isTeamCreator() || $team->getCreator()?->getId() !== $currentCreator->getId()) {
            throw new \RuntimeException('Vous n\'êtes pas le créateur de cette équipe.');
        }

        $newCreator = $this->playerRepository->findById($newCreatorId);

        if ($newCreator === null || $newCreator->getTeam()?->getId() !== $team->getId()) {
            throw new \InvalidArgumentException('Le joueur sélectionné n\'est pas membre de cette équipe.');
        }

        if ($newCreator->getId() === $currentCreator->getId()) {
            return;
        }

        $team->setCreator($newCreator);
        $this->teamRepository->save($team);
    }
}
