<?php

declare(strict_types=1);

namespace App\Application\Player\Service;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Repository\PlayerRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class ActivateAccountService implements ActivateAccountServiceInterface
{
    public function __construct(
        private PlayerRepositoryInterface $playerRepository,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function findByToken(string $token): ?Player
    {
        $player = $this->playerRepository->findByActivationToken($token);

        if (!$player instanceof Player || !$player->isTokenValid()) {
            return null;
        }

        return $player;
    }

    public function activate(Player $player, string $plainPassword): void
    {
        $hashedPassword = $this->passwordHasher->hashPassword($player, $plainPassword);
        $player->setPassword($hashedPassword);
        $player->activate();

        $this->playerRepository->save($player);
    }
}
