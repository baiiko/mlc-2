<?php

declare(strict_types=1);

namespace App\Application\Team\Service;

use App\Application\Team\DTO\CreateTeamDTO;
use App\Domain\Player\Entity\Player;
use App\Domain\Team\Entity\Team;

interface CreateTeamServiceInterface
{
    public function create(CreateTeamDTO $dto, Player $creator): Team;
}
