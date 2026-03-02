<?php

declare(strict_types=1);

namespace App\Domain\Communication\Repository;

use App\Domain\Communication\Entity\Newsletter;

interface NewsletterRepositoryInterface
{
    public function save(Newsletter $newsletter): void;

    public function findById(int $id): ?Newsletter;
}
