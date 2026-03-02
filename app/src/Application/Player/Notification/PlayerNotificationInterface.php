<?php

declare(strict_types=1);

namespace App\Application\Player\Notification;

use App\Domain\Player\Entity\Player;

interface PlayerNotificationInterface
{
    public function sendWelcomeEmail(Player $player, ?string $locale = null): void;

    public function sendPasswordResetEmail(Player $player, ?string $locale = null): void;
}
