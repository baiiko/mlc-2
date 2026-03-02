<?php

declare(strict_types=1);

namespace App\Application\Player\Service;

use App\Domain\Player\Entity\Player;

interface PasswordResetServiceInterface
{
    /**
     * Request a password reset for an email.
     * Will send welcome email if account not activated, or reset email if active.
     * Returns nothing to prevent email enumeration.
     */
    public function requestPasswordReset(string $email, string $locale): void;

    /**
     * Find a player by reset token.
     */
    public function findByResetToken(string $token): ?Player;

    /**
     * Reset password for a player with valid token.
     */
    public function resetPassword(Player $player, string $newPassword): void;
}
