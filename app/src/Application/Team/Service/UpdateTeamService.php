<?php

declare(strict_types=1);

namespace App\Application\Team\Service;

use App\Application\Team\DTO\UpdateTeamDTO;
use App\Domain\Player\Entity\Player;
use App\Domain\Team\Entity\Team;
use App\Domain\Team\Repository\TeamRepositoryInterface;

final readonly class UpdateTeamService implements UpdateTeamServiceInterface
{
    public function __construct(
        private TeamRepositoryInterface $teamRepository,
    ) {
    }

    public function updateTeam(Team $team, Player $player, UpdateTeamDTO $dto): array
    {
        if (!$player->isTeamCreator() || $team->getCreator()?->getId() !== $player->getId()) {
            throw new \RuntimeException('Vous n\'êtes pas le créateur de cette équipe.');
        }

        // Check tag uniqueness
        $existingTeam = $this->teamRepository->findByTag($dto->tag);
        if ($existingTeam !== null && $existingTeam->getId() !== $team->getId()) {
            return [
                'success' => false,
                'error' => 'Une équipe avec ce tag existe déjà.',
            ];
        }

        $team->setTag($dto->tag);
        $team->setFullName($dto->fullName);
        $this->teamRepository->save($team);

        return [
            'success' => true,
            'error' => null,
        ];
    }
}
