<?php

declare(strict_types=1);

namespace App\Domain\Communication\Repository;

use App\Domain\Championship\Entity\Server;
use App\Domain\Communication\Entity\ChatMessage;

interface ChatMessageRepositoryInterface
{
    public function save(ChatMessage $message): void;

    /**
     * @return array<ChatMessage>
     */
    public function findLatestByServer(Server $server, int $limit = 200): array;

    /**
     * @return int Number of deleted rows
     */
    public function deleteOlderThan(\DateTimeImmutable $threshold): int;
}
