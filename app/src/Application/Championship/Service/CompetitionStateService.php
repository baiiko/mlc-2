<?php

declare(strict_types=1);

namespace App\Application\Championship\Service;

use App\Domain\Championship\Entity\Phase;
use App\Domain\Championship\Entity\PhaseType;
use App\Domain\Championship\Entity\Round;
use App\Domain\Championship\Repository\PhaseRepositoryInterface;
use App\Domain\Championship\Repository\RoundRegistrationRepositoryInterface;
use App\Domain\Championship\Repository\RoundRepositoryInterface;
use App\Domain\Championship\Repository\ServerRepositoryInterface;
use App\Domain\Player\Repository\PlayerRepositoryInterface;

final readonly class CompetitionStateService implements CompetitionStateServiceInterface
{
    public function __construct(
        private RoundRepositoryInterface $roundRepository,
        private PhaseRepositoryInterface $phaseRepository,
        private RoundRegistrationRepositoryInterface $registrationRepository,
        private PlayerRepositoryInterface $playerRepository,
        private ServerRepositoryInterface $serverRepository,
    ) {
    }

    public function getActiveRound(): ?Round
    {
        $phase = $this->phaseRepository->findActivePlayablePhase();

        if ($phase instanceof Phase && $phase->getRound() instanceof Round) {
            return $phase->getRound();
        }

        return $this->roundRepository->findCurrentOrUpcoming();
    }

    public function getActivePhase(?PhaseType $type, ?int $group = null): ?Phase
    {
        if ($type === null) {
            return $this->phaseRepository->findActivePlayablePhase();
        }

        return $this->phaseRepository->findActivePhaseByType($type, $group);
    }

    public function canActivate(PhaseType $type, ?int $group = null): bool
    {
        $phase = $this->resolvePhase($type, $group);

        if (!$phase instanceof Phase) {
            return false;
        }

        $now = new \DateTimeImmutable();
        $startAt = $phase->getStartAt();
        $endAt = $phase->getEndAt();

        if (!$startAt instanceof \DateTimeImmutable || $startAt > $now) {
            return false;
        }

        return $endAt === null || $endAt >= $now;
    }

    public function isEnded(PhaseType $type, ?int $group = null): bool
    {
        $phase = $this->resolvePhase($type, $group);

        if (!$phase instanceof Phase) {
            return false;
        }

        $endAt = $phase->getEndAt();

        return $endAt instanceof \DateTimeImmutable && $endAt < new \DateTimeImmutable();
    }

    public function isCompetitor(string $login): bool
    {
        $round = $this->getActiveRound();

        if (!$round instanceof Round) {
            return false;
        }

        $player = $this->playerRepository->findByLogin($login);

        if ($player === null) {
            return false;
        }

        return $this->registrationRepository->findByRoundAndPlayer($round, $player) !== null;
    }

    public function roundToArray(?Round $round): ?array
    {
        if (!$round instanceof Round) {
            return null;
        }

        return [
            'id' => $round->getId(),
            'number' => $round->getNumber(),
            'name' => $round->getName(),
            'qualifyToFinalCount' => $round->getQualifyToFinalCount(),
            'qualifyToSemiCount' => $round->getQualifyToSemiCount(),
            'qualifyFromSemiCount' => $round->getQualifyFromSemiCount(),
            'mapCount' => $this->countQualificationMaps($round),
        ];
    }

    public function phaseToArray(?Phase $phase): ?array
    {
        if (!$phase instanceof Phase) {
            return null;
        }

        return [
            'id' => $phase->getId(),
            'type' => $phase->getType()?->value,
            'groupNumber' => $phase->getGroupNumber(),
            'startAt' => $phase->getStartAt()?->format(\DateTimeInterface::ATOM),
            'endAt' => $phase->getEndAt()?->format(\DateTimeInterface::ATOM),
            'laps' => $phase->getEffectiveLaps(),
            'timeLimit' => $phase->getEffectiveTimeLimit(),
            'finishTimeout' => $phase->getEffectiveFinishTimeout(),
            'warmupDuration' => $phase->getEffectiveWarmupDuration(),
            'players' => $phase->getPlayers() ?? [],
        ];
    }

    public function phaseInfoToArray(?Phase $phase): ?array
    {
        if (!$phase instanceof Phase) {
            return null;
        }

        $round = $phase->getRound();

        return [
            'type' => $phase->getType()?->value,
            'groupNumber' => $phase->getGroupNumber(),
            'players' => $phase->getPlayers() ?? [],
            'qualifyFromSemiCount' => $round?->getQualifyFromSemiCount() ?? 0,
            'qualifyToFinalCount' => $round?->getQualifyToFinalCount() ?? 0,
            'qualifyToSemiCount' => $round?->getQualifyToSemiCount() ?? 0,
        ];
    }

    public function getServerContext(string $serverLogin): ?array
    {
        $server = $this->serverRepository->findByLogin($serverLogin);

        if ($server === null) {
            return null;
        }

        $phase = $this->getActivePhase(null);

        return [
            'server' => [
                'login' => $server->getLogin(),
                'name' => $server->getName(),
            ],
            'round' => $this->roundToArray($this->getActiveRound()),
            'phase' => $this->phaseToArray($phase),
        ];
    }

    /**
     * Number of qualification maps of the round (excluding surprise maps, which
     * are final-only) — the "Y" of the ServerRank "X/Y maps" display, consistent
     * with the qualification ranking's nbMaps cap.
     */
    private function countQualificationMaps(Round $round): int
    {
        $count = 0;

        foreach ($round->getMaps() as $map) {
            if (!$map->isSurprise()) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * Resolve the phase of the given type within the active round, regardless of
     * its time window (used by canActivate/isEnded which apply their own checks).
     */
    private function resolvePhase(PhaseType $type, ?int $group): ?Phase
    {
        $round = $this->getActiveRound();

        if (!$round instanceof Round) {
            return null;
        }

        foreach ($round->getPhases() as $phase) {
            if ($phase->getType() !== $type) {
                continue;
            }

            if ($group !== null && $phase->getGroupNumber() !== $group) {
                continue;
            }

            return $phase;
        }

        return null;
    }
}
