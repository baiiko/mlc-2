<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Admin;

use App\Application\Championship\Service\ServerCommandService;
use App\Domain\Championship\Entity\Server;
use App\Domain\Championship\Repository\ServerRepositoryInterface;
use App\Domain\Communication\Entity\ChatMessage;
use App\Domain\Communication\Enum\ChatMessageType;
use App\Domain\Communication\Repository\ChatMessageRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SERVER_ADMIN')]
final class ServerChatController extends AbstractController
{
    public function __construct(
        private readonly ServerRepositoryInterface $serverRepository,
        private readonly ChatMessageRepositoryInterface $chatMessageRepository,
        private readonly ServerCommandService $serverCommandService,
    ) {
    }

    #[Route('/admin/server/{id}/chat', name: 'admin_server_chat', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function index(int $id): Response
    {
        $server = $this->loadServer($id);
        $messages = $this->chatMessageRepository->findLatestByServer($server, 200);

        return $this->render('admin/server_chat.html.twig', [
            'server' => $server,
            'messages' => array_reverse($messages),
        ]);
    }

    #[Route('/admin/server/{id}/chat/messages', name: 'admin_server_chat_messages', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function messages(int $id): JsonResponse
    {
        $server = $this->loadServer($id);
        $messages = $this->chatMessageRepository->findLatestByServer($server, 200);

        $payload = array_map(fn ($m): array => [
            'id' => $m->getId(),
            'login' => $m->getPlayerLogin(),
            'pseudo' => $m->getPlayerPseudo(),
            'content' => $m->getContent(),
            'type' => $m->getType()->value,
            'createdAt' => $m->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ], array_reverse($messages));

        return new JsonResponse(['messages' => $payload]);
    }

    #[Route('/admin/server/{id}/chat/send', name: 'admin_server_chat_send', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function send(int $id, Request $request): JsonResponse
    {
        $server = $this->loadServer($id);

        $payload = json_decode($request->getContent(), true);
        $message = trim((string) ($payload['message'] ?? $request->request->get('message', '')));

        if ($message === '') {
            return new JsonResponse(['success' => false, 'message' => 'Message vide.'], 400);
        }

        $result = $this->serverCommandService->sendChatMessage($server, $message);

        if ($result['success']) {
            $this->chatMessageRepository->save(new ChatMessage(
                $server,
                $message,
                ChatMessageType::Server,
            ));
        }

        return new JsonResponse($result, $result['success'] ? 200 : 502);
    }

    private function loadServer(int $id): Server
    {
        $server = $this->serverRepository->findById($id);

        if ($server === null) {
            throw new NotFoundHttpException('Serveur non trouvé');
        }

        return $server;
    }
}
