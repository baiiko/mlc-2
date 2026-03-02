<?php

declare(strict_types=1);

namespace App\Application\Player\Service;

use App\Application\Player\DTO\UpdateProfileDTO;
use App\Domain\Player\Entity\Player;

interface UpdateProfileServiceInterface
{
    /**
     * Update a player's profile.
     *
     * @return array{success: bool, error: ?string}
     */
    public function updateProfile(Player $player, UpdateProfileDTO $dto): array;
}
