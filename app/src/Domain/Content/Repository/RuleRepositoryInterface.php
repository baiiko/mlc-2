<?php

declare(strict_types=1);

namespace App\Domain\Content\Repository;

use App\Domain\Content\Entity\Rule;

interface RuleRepositoryInterface
{
    public function findLatest(): ?Rule;
}
