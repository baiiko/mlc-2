<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Championship\Entity\Server;
use App\Domain\Communication\Entity\ChatMessage;
use App\Domain\Communication\Repository\ChatMessageRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineChatMessageRepository implements ChatMessageRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(ChatMessage $message): void
    {
        $this->entityManager->persist($message);
        $this->entityManager->flush();
    }

    public function findLatestByServer(Server $server, int $limit = 200): array
    {
        return $this->entityManager
            ->getRepository(ChatMessage::class)
            ->createQueryBuilder('m')
            ->where('m.server = :server')
            ->setParameter('server', $server)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function deleteOlderThan(\DateTimeImmutable $threshold): int
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->delete(ChatMessage::class, 'm')
            ->where('m.createdAt < :threshold')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->execute();
    }
}
