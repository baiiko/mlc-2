<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api;

use App\Domain\Championship\Repository\ServerRepositoryInterface;
use App\Domain\Communication\Entity\ChatMessage;
use App\Domain\Communication\Enum\ChatMessageType;
use App\Domain\Communication\Repository\ChatMessageRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final readonly class ChatWebhookController
{
    public function __construct(
        private ServerRepositoryInterface $serverRepository,
        private ChatMessageRepositoryInterface $chatMessageRepository,
    ) {
    }

    #[Route('/api/chat', name: 'api_chat_ingest', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        if (!\is_array($payload)) {
            return new JsonResponse(['error' => 'Invalid JSON payload'], 400);
        }

        $serverLogin = $payload['serverLogin'] ?? null;

        if (!\is_string($serverLogin) || $serverLogin === '') {
            return new JsonResponse(['error' => 'Missing or invalid serverLogin'], 400);
        }

        $server = $this->serverRepository->findByLogin($serverLogin);

        if ($server === null) {
            return new JsonResponse(['error' => 'Server not found'], 404);
        }

        $messages = $payload['messages'] ?? null;

        if (!\is_array($messages) || $messages === []) {
            return new JsonResponse(['error' => 'Missing or empty messages'], 400);
        }

        $accepted = 0;

        foreach ($messages as $raw) {
            if (!\is_array($raw)) {
                continue;
            }

            $content = $raw['content'] ?? null;

            if (!\is_string($content) || trim($content) === '') {
                continue;
            }

            $type = ChatMessageType::tryFrom((string) ($raw['type'] ?? 'player')) ?? ChatMessageType::Player;
            $login = isset($raw['login']) && \is_string($raw['login']) && $raw['login'] !== '' ? $raw['login'] : null;
            $pseudo = isset($raw['pseudo']) && \is_string($raw['pseudo']) && $raw['pseudo'] !== '' ? $raw['pseudo'] : null;

            $createdAt = null;

            if (isset($raw['timestamp']) && \is_string($raw['timestamp'])) {
                try {
                    $createdAt = new \DateTimeImmutable($raw['timestamp']);
                } catch (\Exception) {
                    $createdAt = null;
                }
            }

            $this->chatMessageRepository->save(new ChatMessage(
                $server,
                $content,
                $type,
                $login,
                $pseudo,
                $createdAt,
            ));
            ++$accepted;
        }

        return new JsonResponse(['accepted' => $accepted], 201);
    }
}
