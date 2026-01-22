<?php

declare(strict_types=1);

namespace App\Application\Championship\Service;

use App\Domain\Player\Entity\Player;

interface UpdateAvailabilityServiceInterface
{
    /**
     * Update player availability for a round.
     *
     * @throws \RuntimeException if round not found or player not registered
     */
    public function updateAvailability(
        int $roundId,
        Player $player,
        bool $availableSemiFinal1,
        bool $availableSemiFinal2,
        bool $availableFinal
    ): void;
}
