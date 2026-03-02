<?php

declare(strict_types=1);

namespace App\Domain\Championship\Repository;

use App\Domain\Championship\Entity\Phase;
use App\Domain\Championship\Entity\PhaseResult;
use App\Domain\Player\Entity\Player;
use App\Domain\Team\Entity\Team;

interface PhaseResultRepositoryInterface
{
    public function save(PhaseResult $result): void;

    public function delete(PhaseResult $result): void;

    public function findById(int $id): ?PhaseResult;

    public function findByPhaseAndPlayer(Phase $phase, Player $player): ?PhaseResult;

    /**
     * @return PhaseResult[]
     */
    public function findByPhase(Phase $phase): array;

    /**
     * @return PhaseResult[]
     */
    public function findByPhaseOrderedByPosition(Phase $phase): array;

    /**
     * @return PhaseResult[]
     */
    public function findByPhaseAndTeam(Phase $phase, Team $team): array;

    /**
     * @return PhaseResult[]
     */
    public function findQualifiedByPhase(Phase $phase): array;
}
