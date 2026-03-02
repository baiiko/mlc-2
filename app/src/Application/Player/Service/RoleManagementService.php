<?php

declare(strict_types=1);

namespace App\Application\Player\Service;

use App\Domain\Player\Entity\Player;

final readonly class RoleManagementService implements RoleManagementServiceInterface
{
    private const PROTECTED_ROLES = ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'];

    private const ROLES_HIERARCHY = [
        'ROLE_SUPER_ADMIN' => 4,
        'ROLE_ADMIN' => 3,
        'ROLE_MODERATOR' => 2,
        'ROLE_SERVER_ADMIN' => 2,
        'ROLE_PLAYER' => 1,
    ];

    private const ALL_ROLES = [
        'Joueur' => 'ROLE_PLAYER',
        'Modérateur' => 'ROLE_MODERATOR',
        'Admin Serveur' => 'ROLE_SERVER_ADMIN',
        'Administrateur' => 'ROLE_ADMIN',
        'Super Administrateur' => 'ROLE_SUPER_ADMIN',
    ];

    public function getPlayerLevel(Player $player): int
    {
        $maxLevel = 0;
        foreach ($player->getRoles() as $role) {
            $level = self::ROLES_HIERARCHY[$role] ?? 0;
            $maxLevel = max($maxLevel, $level);
        }

        return $maxLevel;
    }

    public function canEditPlayer(Player $editor, Player $target): bool
    {
        return $this->getPlayerLevel($target) < $this->getPlayerLevel($editor);
    }

    public function hasProtectedRole(Player $player): bool
    {
        return count(array_intersect($player->getRoles(), self::PROTECTED_ROLES)) > 0;
    }

    public function getAssignableRoles(Player $admin): array
    {
        $currentLevel = $this->getPlayerLevel($admin);
        $assignableRoles = [];

        foreach (self::ALL_ROLES as $label => $role) {
            $roleLevel = self::ROLES_HIERARCHY[$role] ?? 0;
            // Can only assign roles strictly lower
            if ($roleLevel < $currentLevel) {
                $assignableRoles[$label] = $role;
            }
        }

        return $assignableRoles;
    }
}
