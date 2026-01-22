<?php

declare(strict_types=1);

namespace App\Application\Player\Service;

use App\Domain\Player\Entity\Player;

interface ChangePasswordServiceInterface
{
    /**
     * Change a player's password.
     *
     * @return array{success: bool, error: ?string}
     */
    public function changePassword(Player $player, string $currentPassword, string $newPassword): array;
}
