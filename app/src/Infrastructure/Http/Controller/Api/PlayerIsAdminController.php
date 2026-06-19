<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api;

use App\Domain\Player\Repository\PlayerRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final readonly class PlayerIsAdminController
{
    private const ADMIN_ROLES = ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'];

    public function __construct(
        private PlayerRepositoryInterface $playerRepository,
    ) {
    }

    #[Route('/api/players/{login}/is-admin', name: 'api_players_is_admin', methods: ['GET'])]
    public function __invoke(string $login): JsonResponse
    {
        $player = $this->playerRepository->findByLogin($login);
        $isAdmin = $player !== null && array_intersect(self::ADMIN_ROLES, $player->getRoles()) !== [];

        return new JsonResponse([
            'login' => $login,
            'isAdmin' => $isAdmin,
        ]);
    }
}
