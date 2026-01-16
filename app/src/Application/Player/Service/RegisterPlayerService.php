<?php

declare(strict_types=1);

namespace App\Application\Player\Service;

use App\Application\Player\Command\RegisterPlayerCommand;
use App\Application\Player\DTO\RegisterPlayerDTO;
use App\Application\Player\Handler\RegisterPlayerHandlerInterface;
use App\Domain\Player\Entity\Player;

final readonly class RegisterPlayerService implements RegisterPlayerServiceInterface
{
    public function __construct(
        private RegisterPlayerHandlerInterface $handler,
    ) {
    }

    public function register(RegisterPlayerDTO $dto, string $locale = 'fr'): Player
    {
        $command = new RegisterPlayerCommand(
            login: $dto->login,
            pseudo: $dto->pseudo,
            email: $dto->email,
            discord: $dto->discord,
            newsletter: $dto->newsletter,
            locale: $locale,
        );

        return ($this->handler)($command);
    }
}
