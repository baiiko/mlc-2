<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Application\Championship\Message\UpdateRankingMessage;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route('/api')]
final readonly class RecordNotifyController
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    #[Route('/record/notify', name: 'api_record_notify', methods: ['POST'])]
    public function __invoke(): JsonResponse
    {
        $this->messageBus->dispatch(new UpdateRankingMessage(
            recordedAt: new \DateTimeImmutable(),
        ));

        return new JsonResponse(['status' => 'queued']);
    }
}
