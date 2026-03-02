<?php

declare(strict_types=1);

namespace App\Application\Player\Service;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Repository\PlayerRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class ChangePasswordService implements ChangePasswordServiceInterface
{
    public function __construct(
        private PlayerRepositoryInterface $playerRepository,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function changePassword(Player $player, string $currentPassword, string $newPassword): array
    {
        if (!$this->passwordHasher->isPasswordValid($player, $currentPassword)) {
            return [
                'success' => false,
                'error' => 'Le mot de passe actuel est incorrect.',
            ];
        }

        $hashedPassword = $this->passwordHasher->hashPassword($player, $newPassword);
        $player->setPassword($hashedPassword);
        $this->playerRepository->save($player);

        return [
            'success' => true,
            'error' => null,
        ];
    }
}
