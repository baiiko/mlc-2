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
use App\Domain\Team\Entity\Team;

final readonly class CalculateRankingService implements CalculateRankingServiceInterface
{
    public function __construct(
        private PhaseResultRepositoryInterface $phaseResultRepository,
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
        $minPlayers = $phase->getRound()->getSeason()->getMinPlayersForTeamRanking();
        $teamRankings = [];

        foreach ($teamResults as $teamId => $playerResults) {
            if (count($playerResults) < $minPlayers) {
                continue;
            }

            // Sort by time (ascending)
            usort($playerResults, fn(PhaseResult $a, PhaseResult $b) => $a->getTime() <=> $b->getTime());

            // Take top N players
            $topResults = array_slice($playerResults, 0, $topPlayersCount);
            $totalTime = array_sum(array_map(fn(PhaseResult $r) => $r->getTime(), $topResults));

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
                'playerCount' => count($playerResults),
                'topPlayers' => $topPlayers,
            ];
        }

        // Sort by total time (ascending)
        usort($teamRankings, fn(array $a, array $b) => $a['totalTime'] <=> $b['totalTime']);

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

            $results = $this->phaseResultRepository->findByPhaseOrderedByPosition($finalPhase);

            foreach ($results as $result) {
                $playerId = $result->getPlayer()->getId();
                $points = $this->calculatePoints($result->getPosition());

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

        // Sort by total points (descending)
        uasort($playerScores, fn(array $a, array $b) => $b['totalPoints'] <=> $a['totalPoints']);

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

            $teamRanking = $this->calculatePhaseTeamRanking($finalPhase);

            foreach ($teamRanking as $ranking) {
                $teamId = $ranking->team->getId();
                $points = $this->calculatePoints($ranking->position);

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
        uasort($teamScores, fn(array $a, array $b) => $b['totalPoints'] <=> $a['totalPoints']);

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

    private function calculatePoints(int $position): int
    {
        // Simple points system: more points for better positions
        // Position 1 = 100 points, position 2 = 95, etc. (minimum 1 point)
        return max(1, 101 - $position);
    }

    private function formatTime(int $milliseconds): string
    {
        $minutes = (int) floor($milliseconds / 60000);
        $seconds = (int) floor(($milliseconds % 60000) / 1000);
        $ms = $milliseconds % 1000;

        return sprintf('%d:%02d.%03d', $minutes, $seconds, $ms);
    }
}
