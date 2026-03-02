<?php

declare(strict_types=1);

namespace App\Application\Player\Service;

use App\Application\Player\Notification\PlayerNotificationInterface;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\Repository\PlayerRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class PasswordResetService implements PasswordResetServiceInterface
{
    public function __construct(
        private PlayerRepositoryInterface $playerRepository,
        private PlayerNotificationInterface $playerNotification,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function requestPasswordReset(string $email, string $locale): void
    {
        $player = $this->playerRepository->findByEmail($email);

        if ($player === null) {
            return;
        }

        if (!$player->isActive()) {
            // Account not activated yet, resend welcome email
            $player->generateActivationToken();
            $this->playerRepository->save($player);
            $this->playerNotification->sendWelcomeEmail($player, $locale);
        } else {
            // Account active, send password reset email
            $player->generateResetPasswordToken();
            $this->playerRepository->save($player);
            $this->playerNotification->sendPasswordResetEmail($player, $locale);
        }
    }

    public function findByResetToken(string $token): ?Player
    {
        $player = $this->playerRepository->findByResetPasswordToken($token);

        if ($player === null || !$player->isResetPasswordTokenValid()) {
            return null;
        }

        return $player;
    }

    public function resetPassword(Player $player, string $newPassword): void
    {
        $hashedPassword = $this->passwordHasher->hashPassword($player, $newPassword);
        $player->setPassword($hashedPassword);
        $player->clearResetPasswordToken();
        $this->playerRepository->save($player);
    }
}
