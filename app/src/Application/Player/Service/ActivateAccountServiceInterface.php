<?php

declare(strict_types=1);

namespace App\Application\Player\Service;

use App\Domain\Player\Entity\Player;

interface ActivateAccountServiceInterface
{
    public function findByToken(string $token): ?Player;

    public function activate(Player $player, string $plainPassword): void;
}
