<?php

declare(strict_types=1);

namespace App\Application\Player\Service;

use App\Domain\Player\Entity\Player;

interface ChangeLoginServiceInterface
{
    /**
     * @return array{success: bool, updatedRecords: int, updatedPhases: int, error: ?string}
     */
    public function changeLogin(Player $player, string $newLogin): array;
}
