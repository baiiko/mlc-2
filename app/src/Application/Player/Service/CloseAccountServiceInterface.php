<?php

declare(strict_types=1);

namespace App\Application\Player\Service;

use App\Domain\Player\Entity\Player;

interface CloseAccountServiceInterface
{
    /**
     * Close a player account.
     *
     * @throws \RuntimeException if player is a team creator
     */
    public function closeAccount(Player $player): void;
}
