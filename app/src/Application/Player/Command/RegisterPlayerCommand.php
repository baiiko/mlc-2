<?php

declare(strict_types=1);

namespace App\Application\Player\Command;

final readonly class RegisterPlayerCommand
{
    public function __construct(
        public string $login,
        public string $pseudo,
        public string $email,
        public ?string $discord = null,
        public bool $newsletter = false,
        public string $locale = 'fr',
    ) {
    }
}
