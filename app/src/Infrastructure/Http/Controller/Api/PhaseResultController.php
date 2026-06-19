<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api;

use App\Application\Championship\DTO\PhaseResultNotificationDTO;
use App\Application\Championship\Service\PhaseResultIngestionServiceInterface;
use App\Domain\Championship\Entity\PhaseType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final readonly class PhaseResultController
{
    public function __construct(
        private PhaseResultIngestionServiceInterface $phaseResultIngestion,
    ) {
    }

    #[Route('/api/phase/result', name: 'api_phase_result', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        if (!\is_array($payload)) {
            return new JsonResponse(['error' => 'Invalid JSON payload'], 400);
        }

        $phaseType = PhaseType::tryFrom((string) ($payload['phase']['type'] ?? ''));
        $mapUid = $this->stringOrNull($payload['mapUid'] ?? null);
        $winnerLogin = $this->stringOrNull($payload['winnerLogin'] ?? null);

        if ($phaseType === null || $mapUid === null || $winnerLogin === null) {
            return new JsonResponse(['error' => 'Missing or invalid phase.type, mapUid or winnerLogin'], 400);
        }

        $dto = new PhaseResultNotificationDTO(
            serverLogin: (string) ($payload['serverLogin'] ?? ''),
            phaseType: $phaseType,
            groupNumber: isset($payload['phase']['groupNumber']) ? (int) $payload['phase']['groupNumber'] : null,
            mapUid: $mapUid,
            winnerLogin: $winnerLogin,
            results: $this->normalizeResults($payload['results'] ?? null),
            ts: $this->parseTimestamp($payload['ts'] ?? null),
        );

        $result = $this->phaseResultIngestion->ingest($dto);

        return new JsonResponse($result, $result['accepted'] ? 201 : 200);
    }

    /**
     * @return array<array{login: string, time: int, position: int}>
     */
    private function normalizeResults(mixed $raw): array
    {
        if (!\is_array($raw)) {
            return [];
        }

        $results = [];

        foreach ($raw as $entry) {
            if (!\is_array($entry) || !isset($entry['login']) || !\is_string($entry['login'])) {
                continue;
            }

            $results[] = [
                'login' => $entry['login'],
                'time' => (int) ($entry['time'] ?? 0),
                'position' => (int) ($entry['position'] ?? 0),
            ];
        }

        return $results;
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
