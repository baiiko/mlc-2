<?php

declare(strict_types=1);

namespace App\Domain\Team\Repository;

use App\Domain\Team\Entity\TeamMembership;

interface TeamMembershipRepositoryInterface
{
    public function save(TeamMembership $membership): void;
}
