<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Admin;

use App\Application\Championship\Service\CurrentPhaseResolverService;
use App\Application\Championship\Service\ServerCommandService;
use App\Application\Championship\Service\ServerInfoService;
use App\Domain\Championship\Entity\Server;
use App\Domain\Championship\Enum\ServerPurpose;
use App\Domain\Championship\Repository\ServerRepositoryInterface;
use App\Domain\Communication\Entity\ChatMessage;
use App\Domain\Communication\Enum\ChatMessageType;
use App\Domain\Communication\Repository\ChatMessageRepositoryInterface;
use App\Infrastructure\Service\TmColorParser;
use Psr\Log\LoggerInterface;
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
        private readonly ServerInfoService $serverInfoService,
        private readonly CurrentPhaseResolverService $currentPhaseResolver,
        private readonly LoggerInterface $logger,
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
            'pseudoHtml' => $m->getPlayerPseudo() !== null ? TmColorParser::toHtml($m->getPlayerPseudo()) : null,
            'contentHtml' => TmColorParser::toHtml($m->getContent()),
            'type' => $m->getType()->value,
            'createdAt' => $m->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ], array_reverse($messages));

        return new JsonResponse(['messages' => $payload]);
    }

    #[Route('/admin/server/{id}/chat/maps', name: 'admin_server_chat_maps', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function maps(int $id): JsonResponse
    {
        $server = $this->loadServer($id);
        $info = $this->serverInfoService->getMapList($server);

        $maps = array_map(static fn (array $m): array => [
            'uid' => $m['uid'],
            'nameHtml' => TmColorParser::toHtml($m['name']),
            'nameText' => TmColorParser::stripColors($m['name']),
            'author' => $m['author'],
            'environment' => $m['environment'],
        ], $info['maps']);

        return new JsonResponse([
            'online' => $info['online'],
            'currentUid' => $info['currentUid'],
            'maps' => $maps,
            'error' => $info['error'],
        ]);
    }

    #[Route('/admin/server/{id}/chat/players', name: 'admin_server_chat_players', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function players(int $id): JsonResponse
    {
        $server = $this->loadServer($id);
        $info = $this->serverInfoService->getServerInfo($server);

        $players = array_map(fn (array $p): array => [
            'login' => $p['login'],
            'pseudoHtml' => TmColorParser::toHtml($p['nickname']),
            'pseudoStrip' => TmColorParser::stripColors($p['nickname']),
        ], $info['playerDetails'] ?? []);

        return new JsonResponse([
            'online' => $info['online'] ?? false,
            'playerCount' => $info['playerCount'] ?? 0,
            'maxPlayers' => $info['maxPlayers'] ?? 0,
            'players' => $players,
        ]);
    }

    #[Route('/admin/server/{id}/chat/send', name: 'admin_server_chat_send', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function send(int $id, Request $request): JsonResponse
    {
        $server = $this->loadServer($id);

        $payload = json_decode($request->getContent(), true);
        $message = trim((string) ($payload['message'] ?? $request->request->get('message', '')));
        $login = trim((string) ($payload['login'] ?? $request->request->get('login', '')));

        if ($message === '') {
            return new JsonResponse(['success' => false, 'message' => 'Message vide.'], 400);
        }

        $prefixed = '$f80[Server]$z$s '.$message;

        if ($login !== '') {
            $result = $this->serverCommandService->sendChatMessageToLogin($server, $prefixed, $login);
            $stored = '$f80[Privé → '.$login.']$z$s '.$message;
        } else {
            $result = $this->serverCommandService->sendChatMessage($server, $prefixed);
            $stored = $prefixed;
        }

        if ($result['success']) {
            $this->chatMessageRepository->save(new ChatMessage(
                $server,
                $stored,
                ChatMessageType::Server,
            ));
        }

        return new JsonResponse($result, $result['success'] ? 200 : 502);
    }

    #[Route('/admin/server/{id}/chat/command', name: 'admin_server_chat_command', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function command(int $id, Request $request): JsonResponse
    {
        $server = $this->loadServer($id);
        $payload = json_decode($request->getContent(), true);
        $command = (string) ($payload['command'] ?? $request->request->get('command', ''));
        $target = trim((string) ($payload['target'] ?? $request->request->get('target', '')));

        $isCompetition = $server->getPurpose() === ServerPurpose::Competition;

        $this->logger->info('[ServerChat] Command requested', [
            'serverId' => $server->getId(),
            'serverName' => $server->getName(),
            'serverIp' => $server->getIp(),
            'serverPort' => $server->getPort(),
            'purpose' => $server->getPurpose()->value,
            'command' => $command,
            'target' => $target,
        ]);

        $result = match ($command) {
            'warmup' => $isCompetition ? $this->serverCommandService->toggleWarmUp($server) : null,
            'restart_map' => $this->serverCommandService->restartMap($server),
            'skip_map' => $this->serverCommandService->skipMap($server),
            'phase_qualification' => $isCompetition
                ? $this->serverCommandService->sendChatMessage($server, '/phase qualification')
                : null,
            'reload_match_settings' => $isCompetition
                ? $this->serverCommandService->reloadMatchSettings($server, $this->currentPhaseResolver->resolveCurrentPhaseName())
                : null,
            'reboot' => $this->serverCommandService->rebootServer($server),
            'kick' => $target !== '' ? $this->serverCommandService->kickPlayer($server, $target) : null,
            'force_spectator' => $target !== '' ? $this->serverCommandService->forceSpectator($server, $target, 1) : null,
            'force_player' => $target !== '' ? $this->serverCommandService->forceSpectator($server, $target, 2) : null,
            default => null,
        };

        $this->logger->info('[ServerChat] Command result', [
            'serverId' => $server->getId(),
            'command' => $command,
            'result' => $result,
        ]);

        if ($result === null) {
            return new JsonResponse(['success' => false, 'message' => 'Commande non autorisée.'], 400);
        }

        return new JsonResponse(
            $result + [
                'server' => $server->getName(),
                'target' => $server->getIp() . ':' . $server->getPort(),
                'command' => $command,
            ],
            $result['success'] ? 200 : 502,
        );
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
