<?php

declare(strict_types=1);

namespace App\Domain\Championship\Enum;

enum ServerPurpose: string
{
    case Competition = 'competition';
    case Free = 'free';

    public function getLabel(): string
    {
        return match ($this) {
            self::Competition => 'Compétition',
            self::Free => 'Free (permanent)',
        };
    }
}
