<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api;

use App\Application\Championship\DTO\RecordNotificationDTO;
use App\Application\Championship\Service\RecordIngestionServiceInterface;
use App\Domain\Championship\Entity\PhaseType;
use App\Domain\Championship\Enum\GameMode;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final readonly class RecordController
{
    public function __construct(
        private RecordIngestionServiceInterface $recordIngestion,
    ) {
    }

    #[Route('/api/record', name: 'api_record', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        if (!\is_array($payload)) {
            return new JsonResponse(['error' => 'Invalid JSON payload'], 400);
        }

        $playerLogin = $this->stringOrNull($payload['player']['login'] ?? null);
        $mapUid = $this->stringOrNull($payload['mapUid'] ?? null);
        $phaseType = PhaseType::tryFrom((string) ($payload['phase']['type'] ?? ''));

        if ($playerLogin === null || $mapUid === null || $phaseType === null) {
            return new JsonResponse(['error' => 'Missing or invalid player.login, mapUid or phase.type'], 400);
        }

        $checkpoints = null;

        if (isset($payload['checkpoints']) && \is_array($payload['checkpoints'])) {
            $checkpoints = array_map('intval', array_values($payload['checkpoints']));
        }

        $dto = new RecordNotificationDTO(
            serverLogin: (string) ($payload['serverLogin'] ?? ''),
            playerLogin: $playerLogin,
            playerNickname: $this->stringOrNull($payload['player']['nickname'] ?? null),
            mapUid: $mapUid,
            gameMode: (int) ($payload['gameMode'] ?? GameMode::Laps->value),
            laps: (int) ($payload['laps'] ?? 1),
            time: (int) ($payload['time'] ?? 0),
            phaseType: $phaseType,
            groupNumber: isset($payload['phase']['groupNumber']) ? (int) $payload['phase']['groupNumber'] : null,
            isCompetitor: (bool) ($payload['isCompetitor'] ?? true),
            checkpoints: $checkpoints,
            ts: $this->parseTimestamp($payload['ts'] ?? null),
        );

        $result = $this->recordIngestion->ingest($dto);

        return new JsonResponse($result, $result['accepted'] ? 201 : 200);
    }

    private function stringOrNull(mixed $value): ?string
    {
        return \is_string($value) && $value !== '' ? $value : null;
    }

    private function parseTimestamp(mixed $value): ?\DateTimeImmutable
    {
        if (!\is_string($value) || $value === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }
}
