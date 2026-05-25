<?php

declare(strict_types=1);

namespace App\Domain\Communication\Enum;

enum ChatMessageType: string
{
    case Player = 'player';
    case Server = 'server';
    case System = 'system';
}
