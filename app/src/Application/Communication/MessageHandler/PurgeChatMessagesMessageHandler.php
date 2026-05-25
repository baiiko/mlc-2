<?php

declare(strict_types=1);

namespace App\Application\Communication\MessageHandler;

use App\Application\Communication\Message\PurgeChatMessagesMessage;
use App\Domain\Communication\Repository\ChatMessageRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class PurgeChatMessagesMessageHandler
{
    public function __construct(
        private ChatMessageRepositoryInterface $chatMessageRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(PurgeChatMessagesMessage $message): void
    {
        $threshold = (new \DateTimeImmutable(\sprintf('-%d hours', $message->retentionHours)))
            ->setTimezone(new \DateTimeZone('UTC'));
        $deleted = $this->chatMessageRepository->deleteOlderThan($threshold);

        $this->logger->info('Purged chat messages', [
            'deleted' => $deleted,
            'threshold' => $threshold->format(\DateTimeInterface::ATOM),
        ]);
    }
}
