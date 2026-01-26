<?php

declare(strict_types=1);

namespace App\Application\Championship\Service;

use App\Domain\Championship\Entity\Phase;
use App\Domain\Championship\Entity\PhaseType;
use App\Domain\Championship\Entity\Round;
use App\Domain\Championship\Entity\RoundMap;
use Symfony\Component\Filesystem\Filesystem;

class MatchSettingsGeneratorService
{
    private Filesystem $filesystem;
    private string $matchSettingsPath;

    // Training/Free settings (same as Qualification)
    private const TRAINING_LAPS = 5;
    private const TRAINING_TIME_LIMIT = 210000;
    private const TRAINING_FINISH_TIMEOUT = 30000;
    private const TRAINING_WARMUP = 1;

    public function __construct(string $matchSettingsPath)
    {
        $this->filesystem = new Filesystem();
        $this->matchSettingsPath = $matchSettingsPath;
    }

    /**
     * Generate all MatchSettings files for a round
     * @return array<string, string> Array of generated files [filename => path]
     */
    public function generateAllForRound(Round $round): array
    {
        $generated = [];

        // Generate for each playable phase
        foreach ($round->getPhases() as $phase) {
            if ($phase->getType()?->isPlayable()) {
                $path = $this->saveForPhase($phase);
                $generated[basename($path)] = $path;
            }
        }

        // Generate training.xml
        $path = $this->saveTraining($round);
        $generated['training.xml'] = $path;

        // Generate free.xml
        $path = $this->saveFree($round);
        $generated['free.xml'] = $path;

        return $generated;
    }

    /**
     * Generate MatchSettings for a phase
     */
    public function generateForPhase(Phase $phase): string
    {
        $round = $phase->getRound();
        if ($round === null) {
            throw new \InvalidArgumentException('Phase must be attached to a round');
        }

        $maps = $round->getMaps()->toArray();

        // Filter maps: surprise maps are only included in Final phase
        $includeSurprise = $phase->getType() === PhaseType::Final;
        $maps = array_filter($maps, fn ($map) => $includeSurprise || !$map->isSurprise());

        if (empty($maps)) {
            throw new \InvalidArgumentException('Round has no maps');
        }

        return $this->buildXml(
            $maps,
            $phase->getEffectiveLaps(),
            $phase->getEffectiveTimeLimit(),
            $phase->getEffectiveFinishTimeout(),
            $phase->getEffectiveWarmupDuration()
        );
    }

    /**
     * Save MatchSettings for a phase
     */
    public function saveForPhase(Phase $phase, ?string $filename = null): string
    {
        $xml = $this->generateForPhase($phase);

        if ($filename === null) {
            $filename = match ($phase->getType()) {
                PhaseType::SemiFinal => 'demi.xml',
                PhaseType::Final => 'finale.xml',
                default => 'default.xml',
            };
        }

        return $this->saveFile($filename, $xml);
    }

    /**
     * Generate training.xml - maps of the round, no timeout/warmup
     */
    public function generateTraining(Round $round): string
    {
        $maps = $round->getMaps()->toArray();

        // Exclude surprise maps
        $maps = array_filter($maps, fn ($map) => !$map->isSurprise());

        if (empty($maps)) {
            throw new \InvalidArgumentException('Round has no maps');
        }

        return $this->buildXml(
            $maps,
            self::TRAINING_LAPS,
            self::TRAINING_TIME_LIMIT,
            self::TRAINING_FINISH_TIMEOUT,
            self::TRAINING_WARMUP
        );
    }

    /**
     * Save training.xml
     */
    public function saveTraining(Round $round): string
    {
        $xml = $this->generateTraining($round);
        return $this->saveFile('training.xml', $xml);
    }

    /**
     * Generate free.xml - last 30 maps from the season, no timeout/warmup
     */
    public function generateFree(Round $round, int $limit = 30): string
    {
        $maps = $this->getSeasonMaps($round, $limit);

        if (empty($maps)) {
            throw new \InvalidArgumentException('No maps found for free mode');
        }

        return $this->buildXml(
            $maps,
            self::TRAINING_LAPS,
            self::TRAINING_TIME_LIMIT,
            self::TRAINING_FINISH_TIMEOUT,
            self::TRAINING_WARMUP
        );
    }

    /**
     * Save free.xml
     */
    public function saveFree(Round $round, int $limit = 30): string
    {
        $xml = $this->generateFree($round, $limit);
        return $this->saveFile('free.xml', $xml);
    }

    /**
     * Get last N maps from all rounds of the season
     * @return array<RoundMap>
     */
    private function getSeasonMaps(Round $round, int $limit = 30): array
    {
        $season = $round->getSeason();
        if ($season === null) {
            return array_filter(
                $round->getMaps()->toArray(),
                fn ($map) => !$map->isSurprise()
            );
        }

        $allMaps = [];
        // Get rounds in reverse order (most recent first)
        $rounds = $season->getRounds()->toArray();
        usort($rounds, fn ($a, $b) => ($b->getNumber() ?? 0) <=> ($a->getNumber() ?? 0));

        foreach ($rounds as $seasonRound) {
            foreach ($seasonRound->getMaps() as $map) {
                if (!$map->isSurprise()) {
                    $allMaps[] = $map;
                }
            }
        }

        return array_slice($allMaps, 0, $limit);
    }

    /**
     * Build the XML content
     * @param array<RoundMap> $maps
     */
    private function buildXml(
        array $maps,
        int $laps,
        int $timeLimit,
        int $finishTimeout,
        int $warmupDuration
    ): string {
        $xml = '<?xml version="1.0" encoding="utf-8" ?>' . "\n";
        $xml .= '<playlist>' . "\n";
        $xml .= '    <gameinfos>' . "\n";
        $xml .= '        <game_mode>3</game_mode>' . "\n";
        $xml .= '        <chat_time>20000</chat_time>' . "\n";
        $xml .= '        <finishtimeout>' . $finishTimeout . '</finishtimeout>' . "\n";
        $xml .= '        <allwarmupduration>' . $warmupDuration . '</allwarmupduration>' . "\n";
        $xml .= '        <disablerespawn>0</disablerespawn>' . "\n";
        $xml .= '        <forceshowallopponents>0</forceshowallopponents>' . "\n";
        $xml .= '        <rounds_pointslimit>30</rounds_pointslimit>' . "\n";
        $xml .= '        <rounds_usenewrules>0</rounds_usenewrules>' . "\n";
        $xml .= '        <rounds_forcedlaps>0</rounds_forcedlaps>' . "\n";
        $xml .= '        <rounds_pointslimitnewrules>0</rounds_pointslimitnewrules>' . "\n";
        $xml .= '        <team_pointslimit>50</team_pointslimit>' . "\n";
        $xml .= '        <team_maxpoints>6</team_maxpoints>' . "\n";
        $xml .= '        <team_usenewrules>0</team_usenewrules>' . "\n";
        $xml .= '        <team_pointslimitnewrules>0</team_pointslimitnewrules>' . "\n";
        $xml .= '        <timeattack_limit>180000</timeattack_limit>' . "\n";
        $xml .= '        <timeattack_synchstartperiod>0</timeattack_synchstartperiod>' . "\n";
        $xml .= '        <laps_nblaps>' . $laps . '</laps_nblaps>' . "\n";
        $xml .= '        <laps_timelimit>' . $timeLimit . '</laps_timelimit>' . "\n";
        $xml .= '        <cup_pointslimit>100</cup_pointslimit>' . "\n";
        $xml .= '        <cup_roundsperchallenge>5</cup_roundsperchallenge>' . "\n";
        $xml .= '        <cup_nbwinners>3</cup_nbwinners>' . "\n";
        $xml .= '        <cup_warmupduration>2</cup_warmupduration>' . "\n";
        $xml .= '    </gameinfos>' . "\n";
        $xml .= '    <startindex>0</startindex>' . "\n";

        foreach ($maps as $map) {
            $uid = $map->getUid();
            if ($uid === null) {
                continue;
            }

            // Build map path from the map's own round
            $mapRound = $map->getRound();
            $mapSeason = $mapRound?->getSeason();
            $mapPath = ($mapSeason?->getId() ?? 'default') . '/' . ($mapRound?->getName() ?? 'round') . '/';

            $xml .= '    <challenge>' . "\n";
            $xml .= '        <file>' . $mapPath . $uid . '.Challenge.Gbx</file>' . "\n";
            $xml .= '        <ident>' . $uid . '</ident>' . "\n";
            $xml .= '    </challenge>' . "\n";
        }

        $xml .= '</playlist>';

        return $xml;
    }

    /**
     * Save XML content to file
     */
    private function saveFile(string $filename, string $content): string
    {
        $destPath = rtrim($this->matchSettingsPath, '/') . '/' . $filename;

        $dir = dirname($destPath);
        if (!$this->filesystem->exists($dir)) {
            $this->filesystem->mkdir($dir, 0755);
        }

        $this->filesystem->dumpFile($destPath, $content);

        return $destPath;
    }

    public function getMatchSettingsPath(): string
    {
        return $this->matchSettingsPath;
    }
}
