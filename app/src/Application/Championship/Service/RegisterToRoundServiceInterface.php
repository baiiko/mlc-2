<?php

declare(strict_types=1);

namespace App\Application\Championship\Service;

use App\Application\Championship\DTO\RegisterToRoundDTO;
use App\Domain\Championship\Entity\RoundRegistration;

interface RegisterToRoundServiceInterface
{
    public function register(RegisterToRoundDTO $dto): RoundRegistration;

    public function isAlreadyRegistered(RegisterToRoundDTO $dto): bool;
}
