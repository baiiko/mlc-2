<?php

declare(strict_types=1);

namespace App\Application\Player\Handler;

use App\Application\Player\Command\RegisterPlayerCommand;
use App\Domain\Player\Entity\Player;

interface RegisterPlayerHandlerInterface
{
    public function __invoke(RegisterPlayerCommand $command): Player;
}
