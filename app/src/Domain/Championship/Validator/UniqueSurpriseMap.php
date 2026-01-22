<?php

declare(strict_types=1);

namespace App\Domain\Championship\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class UniqueSurpriseMap extends Constraint
{
    public string $message = 'Cette manche a déjà une map surprise.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
