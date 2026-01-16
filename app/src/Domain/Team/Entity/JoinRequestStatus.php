<?php

declare(strict_types=1);

namespace App\Domain\Team\Entity;

enum JoinRequestStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
}
