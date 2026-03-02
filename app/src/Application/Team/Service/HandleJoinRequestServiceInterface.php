<?php

declare(strict_types=1);

namespace App\Application\Team\Service;

use App\Domain\Player\Entity\Player;
use App\Domain\Team\Entity\TeamJoinRequest;

interface HandleJoinRequestServiceInterface
{
    /**
     * Accept a join request.
     *
     * @throws \RuntimeException if player is not the team creator or request doesn't belong to their team
     */
    public function acceptRequest(int $requestId, Player $teamCreator): void;

    /**
     * Reject a join request.
     *
     * @throws \RuntimeException if player is not the team creator or request doesn't belong to their team
     */
    public function rejectRequest(int $requestId, Player $teamCreator): void;

    /**
     * Find a join request by ID.
     */
    public function findById(int $id): ?TeamJoinRequest;
}
