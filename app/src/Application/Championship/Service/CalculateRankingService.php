<?php

declare(strict_types=1);

namespace App\Application\Championship\Service;

use App\Application\Championship\DTO\IndividualRankingDTO;
use App\Application\Championship\DTO\SeasonRankingDTO;
use App\Application\Championship\DTO\TeamRankingDTO;
use App\Domain\Championship\Entity\Phase;
use App\Domain\Championship\Entity\PhaseResult;
use App\Domain\Championship\Entity\PhaseType;
use App\Domain\Championship\Entity\Season;
use App\Domain\Championship\Repository\PhaseResultRepositoryInterface;
use App\Domain\Championship\Repository\RoundRegistrationRepositoryInterface;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\Repository\PlayerRepositoryInterface;
use App\Domain\Team\Entity\Team;

final readonly class CalculateRankingService implements CalculateRankingServiceInterface
{
    private const TEAM_BONUS = [1 => 10, 2 => 8, 3 => 6, 4 => 5, 5 => 4, 6 => 3, 7 => 2, 8 => 1];

    public function __construct(
        private PhaseResultRepositoryInterface $phaseResultRepository,
        private PlayerRepositoryInterface $playerRepository,
        private RoundRegistrationRepositoryInterface $registrationRepository,
    ) {
    }

    public function calculatePhaseIndividualRanking(Phase $phase): array
    {
        $results = $this->phaseResultRepository->findByPhaseOrderedByPosition($phase);
        $rankings = [];

        foreach ($results as $result) {
            $rankings[] = new IndividualRankingDTO(
                position: $result->getPosition(),
                player: $result->getPlayer(),
                team: $result->getRegistration()->getTeam(),
                time: $result->getTime(),
                formattedTime: $result->getFormattedTime(),
                isQualified: $result->isQualified(),
                qualifiedTo: $result->getQualifiedTo()?->getLabel(),
            );
        }

        return $rankings;
    }

    public function calculatePhaseTeamRanking(Phase $phase, int $topPlayersCount = 4): array
    {
        // For final phases with stored per-map data, use the map-based formula
        if ($phase->getType() === PhaseType::Final) {
            return $this->calculateFinalTeamRanking($phase);
        }

        $results = $this->phaseResultRepository->findByPhaseOrderedByPosition($phase);

        // Group results by team
        /** @var array<int, PhaseResult[]> $teamResults */
        $teamResults = [];
        /** @var array<int, Team> $teams */
        $teams = [];

        foreach ($results as $result) {
            $team = $result->getRegistration()->getTeam();

            if ($team === null) {
                continue;
            }

            $teamId = $team->getId();

            if (!isset($teamResults[$teamId])) {
                $teamResults[$teamId] = [];
                $teams[$teamId] = $team;
            }

            $teamResults[$teamId][] = $result;
        }

        // Calculate team rankings
        $minPlayers = $phase->getType() === PhaseType::SemiFinal
            ? 2
            : $phase->getRound()->getSeason()->getMinPlayersForTeamRanking();
        $teamRankings = [];

        foreach ($teamResults as $teamId => $playerResults) {
            if (\count($playerResults) < $minPlayers) {
                continue;
            }

            // Sort by time (ascending)
            usort($playerResults, fn (PhaseResult $a, PhaseResult $b): int => $a->getTime() <=> $b->getTime());

            // Take top N players
            $topResults = \array_slice($playerResults, 0, $topPlayersCount);
            $totalTime = array_sum(array_map(fn (PhaseResult $r): int => $r->getTime(), $topResults));

            $topPlayers = [];

            foreach ($topResults as $result) {
                $topPlayers[] = new IndividualRankingDTO(
                    position: $result->getPosition(),
                    player: $result->getPlayer(),
                    team: $teams[$teamId],
                    time: $result->getTime(),
                    formattedTime: $result->getFormattedTime(),
                );
            }

            $teamRankings[] = [
                'team' => $teams[$teamId],
                'totalTime' => $totalTime,
                'playerCount' => \count($playerResults),
                'topPlayers' => $topPlayers,
            ];
        }

        // Sort by total time (ascending)
        usort($teamRankings, fn (array $a, array $b): int => $a['totalTime'] <=> $b['totalTime']);

        // Build DTOs with positions
        $rankings = [];
        $position = 1;

        foreach ($teamRankings as $data) {
            $rankings[] = new TeamRankingDTO(
                position: $position++,
                team: $data['team'],
                totalTime: $data['totalTime'],
                formattedTime: $this->formatTime($data['totalTime']),
                playerCount: $data['playerCount'],
                topPlayers: $data['topPlayers'],
            );
        }

        return $rankings;
    }

    public function calculateSeasonIndividualRanking(Season $season): array
    {
        $playerScores = [];

        foreach ($season->getRounds() as $round) {
            $finalPhase = $round->getPhaseByType(PhaseType::Final);

            if ($finalPhase === null) {
                continue;
            }

            $storedRanking = $finalPhase->getRanking();

            if ($storedRanking !== null && isset($storedRanking['ranking'])) {
                $entries = $storedRanking['ranking'];

                // Separate finalists and non-finalists
                $finalists = [];
                $nonFinalists = [];

                foreach ($entries as $i => $entry) {
                    if (($entry['bf'] ?? 0) > 0) {
                        $finalists[] = ['index' => $i, 'entry' => $entry];
                    } else {
                        $nonFinalists[] = ['index' => $i, 'entry' => $entry];
                    }
                }

                // Non-finalists: dense ranking among themselves (ties get same score)
                $nfDenseRank = 0;
                $prevTotal = null;
                $nfScores = [];
                $numDistinctNF = 0;

                foreach ($nonFinalists as $nf) {
                    if ($nf['entry']['total'] !== $prevTotal) {
                        ++$nfDenseRank;
                    }
                    $nfScores[$nf['index']] = $nfDenseRank;
                    $prevTotal = $nf['entry']['total'];
                    $numDistinctNF = $nfDenseRank;
                }

                // Finalists: sequential ranking (no ties, order from stored ranking)
                $top10Bonus = [1 => 25, 2 => 20, 3 => 15, 4 => 10, 5 => 8, 6 => 6, 7 => 5, 8 => 3, 9 => 2, 10 => 2];
                $finalistBonus = 75;
                $numFinalists = \count($finalists);
                $finScores = [];

                foreach ($finalists as $rank => $f) {
                    $finIndex = $rank + 1; // 1-based
                    $base = ($numDistinctNF + 1) + $finalistBonus + ($numFinalists - $finIndex);
                    $t10 = $top10Bonus[$finIndex] ?? 0;
                    $finScores[$f['index']] = $base + $t10;
                }

                foreach ($entries as $i => $entry) {
                    $login = $entry['login'];
                    $player = $this->playerRepository->findByLogin($login);

                    if (!$player instanceof Player) {
                        continue;
                    }

                    $points = isset($finScores[$i]) ? $finScores[$i] : $numDistinctNF - $nfScores[$i] + 1;

                    $playerId = $player->getId();

                    if (!isset($playerScores[$playerId])) {
                        $playerScores[$playerId] = [
                            'player' => $player,
                            'totalPoints' => 0,
                            'roundScores' => [],
                        ];
                    }

                    $playerScores[$playerId]['totalPoints'] += $points;
                    $playerScores[$playerId]['roundScores'][$round->getId()] = $points;
                }
            } else {
                // Fallback: calculate from phase results
                $results = $this->phaseResultRepository->findByPhaseOrderedByPosition($finalPhase);

                foreach ($results as $result) {
                    $playerId = $result->getPlayer()->getId();
                    $points = max(1, 101 - $result->getPosition());

                    if (!isset($playerScores[$playerId])) {
                        $playerScores[$playerId] = [
                            'player' => $result->getPlayer(),
                            'totalPoints' => 0,
                            'roundScores' => [],
                        ];
                    }

                    $playerScores[$playerId]['totalPoints'] += $points;
                    $playerScores[$playerId]['roundScores'][$round->getId()] = $points;
                }
            }
        }

        // Sort by total points (descending)
        uasort($playerScores, fn (array $a, array $b): int => $b['totalPoints'] <=> $a['totalPoints']);

        // Build DTOs with positions
        $rankings = [];
        $position = 1;

        foreach ($playerScores as $data) {
            $rankings[] = new SeasonRankingDTO(
                position: $position++,
                entity: $data['player'],
                totalPoints: $data['totalPoints'],
                roundScores: $data['roundScores'],
            );
        }

        return $rankings;
    }

    public function calculateSeasonTeamRanking(Season $season): array
    {
        $teamScores = [];

        foreach ($season->getRounds() as $round) {
            $finalPhase = $round->getPhaseByType(PhaseType::Final);

            if ($finalPhase === null) {
                continue;
            }

            $teamRanking = $this->calculateFinalTeamRanking($finalPhase);

            foreach ($teamRanking as $ranking) {
                $teamId = $ranking->team->getId();
                $points = $ranking->roundPoints;

                if (!isset($teamScores[$teamId])) {
                    $teamScores[$teamId] = [
                        'team' => $ranking->team,
                        'totalPoints' => 0,
                        'roundScores' => [],
                    ];
                }

                $teamScores[$teamId]['totalPoints'] += $points;
                $teamScores[$teamId]['roundScores'][$round->getId()] = $points;
            }
        }

        // Sort by total points (descending)
        uasort($teamScores, fn (array $a, array $b): int => $b['totalPoints'] <=> $a['totalPoints']);

        // Build DTOs with positions
        $rankings = [];
        $position = 1;

        foreach ($teamScores as $data) {
            $rankings[] = new SeasonRankingDTO(
                position: $position++,
                entity: $data['team'],
                totalPoints: $data['totalPoints'],
                roundScores: $data['roundScores'],
            );
        }

        return $rankings;
    }

    /**
     * Calculate team ranking for the final phase using per-map results.
     *
     * Formula:
     * 1. Per map: each player scores (65 - place). Top 2 per team per map.
     * 2. Raw total = sum of all map points.
     * 3. Round points = numTeams - (position - 1) + bonus
     *    Bonus: 1st=10, 2nd=8, 3rd=6, 4th=5, 5th=4, 6th=3, 7th=2, 8th=1, 9th+=0
     *
     * @return TeamRankingDTO[]
     */
    private function calculateFinalTeamRanking(Phase $phase): array
    {
        $storedRanking = $phase->getRanking();

        if ($storedRanking === null || !isset($storedRanking['ranking'])) {
            return [];
        }

        $entries = $storedRanking['ranking'];

        // Build login → team mapping from registrations
        $registrations = $this->registrationRepository->findByRound($phase->getRound());
        $loginToTeam = [];

        foreach ($registrations as $reg) {
            $team = $reg->getTeam();

            if ($team !== null) {
                $loginToTeam[mb_strtolower($reg->getPlayer()->getLogin())] = $team;
            }
        }

        // Calculate per-map points for each team (top 2 per map)
        /** @var array<int, array<string, list<int>>> $teamMapPoints teamId => mapId => [points...] */
        $teamMapPoints = [];
        /** @var array<int, Team> $teams */
        $teams = [];
        /** @var array<int, int> $teamPlayerCount */
        $teamPlayerCount = [];

        foreach ($entries as $entry) {
            $team = $loginToTeam[mb_strtolower($entry['login'])] ?? null;

            if ($team === null) {
                continue;
            }

            $teamId = $team->getId();
            $teams[$teamId] = $team;

            if (!isset($teamPlayerCount[$teamId])) {
                $teamPlayerCount[$teamId] = 0;
            }

            $hasMapResults = false;

            foreach ($entry['maps'] ?? [] as $mapId => $mapResult) {
                $place = $mapResult['place'] ?? 0;

                if ($place <= 0) {
                    continue;
                }
                $hasMapResults = true;
                $teamMapPoints[$teamId][$mapId][] = 65 - $place;
            }

            if ($hasMapResults) {
                ++$teamPlayerCount[$teamId];
            }
        }

        // For each team: take top 2 per map, sum across all maps
        $teamTotals = [];

        foreach ($teamMapPoints as $teamId => $mapPoints) {
            if (($teamPlayerCount[$teamId] ?? 0) < 1) {
                continue;
            }

            $total = 0;

            foreach ($mapPoints as $playerPoints) {
                rsort($playerPoints);
                $top2 = \array_slice($playerPoints, 0, 2);
                $total += array_sum($top2);
            }
            $teamTotals[$teamId] = $total;
        }

        // Sort by raw total descending
        arsort($teamTotals);

        // Build DTOs with position and round points
        $numTeams = \count($teamTotals);
        $rankings = [];
        $position = 1;

        foreach ($teamTotals as $teamId => $rawTotal) {
            $bonus = self::TEAM_BONUS[$position] ?? 0;
            $roundPoints = $numTeams - ($position - 1) + $bonus;

            $rankings[] = new TeamRankingDTO(
                position: $position,
                team: $teams[$teamId],
                totalTime: $rawTotal,
                formattedTime: (string) $rawTotal,
                playerCount: $teamPlayerCount[$teamId],
                topPlayers: [],
                roundPoints: $roundPoints,
            );
            ++$position;
        }

        return $rankings;
    }

    private function formatTime(int $milliseconds): string
    {
        $minutes = (int) floor($milliseconds / 60000);
        $seconds = (int) floor(($milliseconds % 60000) / 1000);
        $ms = $milliseconds % 1000;

        return \sprintf('%d:%02d.%03d', $minutes, $seconds, $ms);
    }
}
