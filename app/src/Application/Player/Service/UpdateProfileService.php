<?php

declare(strict_types=1);

namespace App\Application\Player\Service;

use App\Application\Player\DTO\UpdateProfileDTO;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\Repository\PlayerRepositoryInterface;

final readonly class UpdateProfileService implements UpdateProfileServiceInterface
{
    public function __construct(
        private PlayerRepositoryInterface $playerRepository,
    ) {
    }

    public function updateProfile(Player $player, UpdateProfileDTO $dto): array
    {
        // Check email uniqueness (excluding current player)
        if ($dto->email !== $player->getEmail()) {
            $existingPlayer = $this->playerRepository->findByEmail($dto->email);
            if ($existingPlayer !== null) {
                return [
                    'success' => false,
                    'error' => 'Cette adresse email est déjà utilisée.',
                ];
            }
        }

        $player->setPseudo($dto->pseudo);
        $player->setEmail($dto->email);
        $player->setDiscord($dto->discord);
        $player->setNewsletter($dto->newsletter);
        $this->playerRepository->save($player);

        return [
            'success' => true,
            'error' => null,
        ];
    }
}
