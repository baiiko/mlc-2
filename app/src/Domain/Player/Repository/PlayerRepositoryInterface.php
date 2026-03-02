<?php

declare(strict_types=1);

namespace App\Domain\Player\Repository;

use App\Domain\Player\Entity\Player;

interface PlayerRepositoryInterface
{
    public function save(Player $player): void;

    public function findById(int $id): ?Player;

    public function findByLogin(string $login): ?Player;

    public function findByEmail(string $email): ?Player;

    public function findByActivationToken(string $token): ?Player;

    public function findByResetPasswordToken(string $token): ?Player;

    public function existsByLogin(string $login): bool;

    public function existsByEmail(string $email): bool;

    public function delete(Player $player): void;

    /**
     * @param array<string> $logins
     *
     * @return Player[]
     */
    public function findByLogins(array $logins): array;
}
