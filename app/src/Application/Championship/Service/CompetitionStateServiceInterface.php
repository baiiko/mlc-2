<?php

declare(strict_types=1);

namespace App\Application\Championship\Service;

use App\Domain\Championship\Entity\Phase;
use App\Domain\Championship\Entity\PhaseType;
use App\Domain\Championship\Entity\Round;

/**
 * Read-only resolution of the current competition state for the external game
 * server ("yuggy"). Resolution is purely time-based on the active season: a
 * server login never determines which round/phase/group is current — only the
 * schedule (phase startAt/endAt windows) does.
 */
interface CompetitionStateServiceInterface
{
    /**
     * The round currently in play (round of the active playable phase), or the
     * admin-flagged current/upcoming round when nothing is running.
     */
    public function getActiveRound(): ?Round;

    /**
     * The phase currently running. When $type is null, returns any active
     * playable phase; otherwise restricts to that type (and group if given).
     */
    public function getActivePhase(?PhaseType $type, ?int $group = null): ?Phase;

    /**
     * Whether the phase of the given type has reached its start time and has not
     * ended yet — i.e. the server may switch into it.
     */
    public function canActivate(PhaseType $type, ?int $group = null): bool;

    /**
     * Whether the phase of the given type is past its end time.
     */
    public function isEnded(PhaseType $type, ?int $group = null): bool;

    /**
     * Whether the player is a registered competitor for the active round.
     */
    public function isCompetitor(string $login): bool;

    /**
     * @return array{id: int|null, number: int|null, name: string|null, qualifyToFinalCount: int, qualifyToSemiCount: int, qualifyFromSemiCount: int}|null
     */
    public function roundToArray(?Round $round): ?array;

    /**
     * @return array{id: int|null, type: string|null, groupNumber: int|null, startAt: string|null, endAt: string|null, laps: int, timeLimit: int, finishTimeout: int, warmupDuration: int, players: array<string>}|null
     */
    public function phaseToArray(?Phase $phase): ?array;

    /**
     * @return array{type: string|null, groupNumber: int|null, players: array<string>, qualifyFromSemiCount: int, qualifyToFinalCount: int, qualifyToSemiCount: int}|null
     */
    public function phaseInfoToArray(?Phase $phase): ?array;

    /**
     * Aggregated current state for a server (round + active phase) in one call.
     * Returns null when the server login is unknown.
     *
     * @return array<string, mixed>|null
     */
    public function getServerContext(string $serverLogin): ?array;
}
