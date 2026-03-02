<?php

declare(strict_types=1);

namespace App\Domain\Championship\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class UniqueActiveRound extends Constraint
{
    public string $message = 'La manche "{{ round }}" est déjà active. Désactivez-la avant d\'en activer une autre.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
