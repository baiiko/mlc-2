<?php

declare(strict_types=1);

namespace App\Application\Player\Service;

use App\Application\Player\DTO\RegisterPlayerDTO;
use App\Domain\Player\Entity\Player;

interface RegisterPlayerServiceInterface
{
    public function register(RegisterPlayerDTO $dto, string $locale = 'fr'): Player;
}
