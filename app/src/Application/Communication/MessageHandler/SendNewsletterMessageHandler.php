<?php

declare(strict_types=1);

namespace App\Application\Communication\MessageHandler;

use App\Application\Communication\Message\SendNewsletterMessage;
use App\Application\Communication\Service\NewsletterSendingService;
use App\Domain\Communication\Repository\NewsletterRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SendNewsletterMessageHandler
{
    public function __construct(
        private NewsletterRepositoryInterface $newsletterRepository,
        private NewsletterSendingService $newsletterSendingService,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(SendNewsletterMessage $message): void
    {
        $newsletter = $this->newsletterRepository->findById($message->newsletterId);

        if ($newsletter === null) {
            $this->logger->warning('[Newsletter] dropped: newsletter not found', [
                'id' => $message->newsletterId,
            ]);

            return;
        }

        if ($newsletter->getSentAt() !== null) {
            $this->logger->info('[Newsletter] skipped: already sent', [
                'id' => $message->newsletterId,
                'sentAt' => $newsletter->getSentAt()->format('c'),
            ]);

            return;
        }

        $count = $this->newsletterSendingService->send($newsletter);

        $this->logger->info('[Newsletter] sent', [
            'id' => $message->newsletterId,
            'recipientCount' => $count,
        ]);
    }
}
