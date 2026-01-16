<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Repository\PlayerRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrinePlayerRepository implements PlayerRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Player $player): void
    {
        $this->entityManager->persist($player);
        $this->entityManager->flush();
    }

    public function findById(int $id): ?Player
    {
        return $this->entityManager
            ->getRepository(Player::class)
            ->find($id);
    }

    public function findByLogin(string $login): ?Player
    {
        return $this->entityManager
            ->getRepository(Player::class)
            ->findOneBy(['login' => $login]);
    }

    public function findByEmail(string $email): ?Player
    {
        return $this->entityManager
            ->getRepository(Player::class)
            ->findOneBy(['email' => $email]);
    }

    public function findByActivationToken(string $token): ?Player
    {
        return $this->entityManager
            ->getRepository(Player::class)
            ->findOneBy(['activationToken' => $token]);
    }

    public function findByResetPasswordToken(string $token): ?Player
    {
        return $this->entityManager
            ->getRepository(Player::class)
            ->findOneBy(['resetPasswordToken' => $token]);
    }

    public function existsByLogin(string $login): bool
    {
        return $this->findByLogin($login) !== null;
    }

    public function existsByEmail(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    public function delete(Player $player): void
    {
        $this->entityManager->remove($player);
        $this->entityManager->flush();
    }
}
