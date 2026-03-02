<?php

declare(strict_types=1);

namespace App\Application\Player\Service;

use App\Domain\Player\Entity\Player;

interface RoleManagementServiceInterface
{
    /**
     * Get the role level for a player.
     */
    public function getPlayerLevel(Player $player): int;

    /**
     * Check if a player can edit another player (based on role hierarchy).
     */
    public function canEditPlayer(Player $editor, Player $target): bool;

    /**
     * Check if a player has a protected role (admin/super admin).
     */
    public function hasProtectedRole(Player $player): bool;

    /**
     * Get roles that a player can assign to others.
     *
     * @return array<string, string> label => role
     */
    public function getAssignableRoles(Player $admin): array;
}
