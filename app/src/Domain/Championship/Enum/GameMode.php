<?php

declare(strict_types=1);

namespace App\Domain\Championship\Enum;

enum GameMode: int
{
    case Rounds = 0;
    case TimeAttack = 1;
    case Team = 2;
    case Laps = 3;
    case Stunts = 4;
    case Cup = 5;

    public function label(): string
    {
        return match ($this) {
            self::Rounds => 'Rounds',
            self::TimeAttack => 'Time Attack',
            self::Team => 'Team',
            self::Laps => 'Laps',
            self::Stunts => 'Stunts',
            self::Cup => 'Cup',
        };
    }
}
