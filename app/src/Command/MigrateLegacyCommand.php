<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate-legacy',
    description: 'Migrate data from legacy MLC database to new schema',
)]
class MigrateLegacyCommand extends Command
{
    private const LEGACY_DB = 'mlc_legacy';

    private const CHAMP_ID = 35;

    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Migration Legacy MLC → Nouveau schéma');

        $this->connection->createQueryBuilder();

        try {
            $this->connection->beginTransaction();

            // 1. Import players
            $playerIdMap = $this->importPlayers($io);

            // 2. Import teams + memberships
            $teamIdMap = $this->importTeams($io, $playerIdMap);

            // 3. Create season
            $seasonId = $this->createSeason($io);

            // 4. Create round
            $roundId = $this->createRound($io, $seasonId);

            // 5. Create phases
            $phaseIds = $this->createPhases($io, $roundId);

            // 6. Create round registrations
            $registrationMap = $this->createRegistrations($io, $roundId, $playerIdMap, $teamIdMap);

            // 7. Import maps
            $mapIdMap = $this->importMaps($io, $roundId);

            // 8. Import map records (qualification lap times)
            $this->importMapRecords($io, $roundId);

            // 9. Import phase results (semi-finals from course1, finals from course2)
            $this->importPhaseResults($io, $phaseIds, $playerIdMap, $registrationMap);

            // 10. Import qualification ranking into phase
            $this->importQualificationRanking($io, $phaseIds);

            // 11. Import final ranking into phase
            $this->importFinalRanking($io, $phaseIds);

            $this->connection->commit();
            $io->success('Migration terminée avec succès !');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->connection->rollBack();
            $io->error('Erreur: ' . $e->getMessage());
            $io->error($e->getTraceAsString());

            return Command::FAILURE;
        }
    }

    private function importPlayers(SymfonyStyle $io): array
    {
        $io->section('Import des joueurs');

        $players = $this->connection->fetchAllAssociative(
            'SELECT id, login, pseudo, pseudoTMN, email, discord, actif, mdp, date_inscription
             FROM ' . self::LEGACY_DB . ".joueur
             WHERE actif = 'Oui'"
        );

        // Build nickname map from live server (fallback when pseudoTMN has no TM codes)
        $nicknameMap = $this->connection->fetchAllKeyValue(
            'SELECT LOWER(login), nickname FROM ' . self::LEGACY_DB . '.players'
        );

        $idMap = []; // legacy_id => new_id
        $seenLogins = [];
        $seenEmails = [];
        $now = date('Y-m-d H:i:s');

        foreach ($players as $p) {
            $login = trim($p['login']);
            $email = trim($p['email']);
            // Use pseudoTMN if it has TM color codes ($), otherwise players.nickname, fallback to pseudo
            $pseudoTMN = $p['pseudoTMN'] ?? '';
            $hasTmCodes = str_contains($pseudoTMN, '$');
            $pseudo = $hasTmCodes ? $pseudoTMN : ($nicknameMap[mb_strtolower($login)] ?? $p['pseudo']);
            $pseudo = mb_substr(trim($pseudo), 0, 50);

            // Skip duplicates (case-insensitive for login, MySQL collation)
            $loginLower = mb_strtolower($login);
            $emailLower = mb_strtolower($email);

            if ($login === '') {
                continue;
            }

            if ($login === '0') {
                continue;
            }

            if ($email === '') {
                continue;
            }

            if ($email === '0') {
                continue;
            }

            if (isset($seenLogins[$loginLower])) {
                continue;
            }

            if (isset($seenEmails[$emailLower])) {
                continue;
            }
            $seenLogins[$loginLower] = true;
            $seenEmails[$emailLower] = true;

            $discord = empty($p['discord']) ? null : mb_substr(trim($p['discord']), 0, 100);

            $this->connection->insert('player', [
                'login' => $login,
                'email' => $email,
                'pseudo' => $pseudo,
                'password' => $p['mdp'],
                'discord' => $discord,
                'is_active' => 1,
                'newsletter' => 0,
                'roles' => '[]',
                'created_at' => $p['date_inscription'] ?: $now,
                'updated_at' => $now,
            ]);

            $newId = (int) $this->connection->lastInsertId();
            $idMap[(int) $p['id']] = $newId;
        }

        $io->info(\sprintf('  %d joueurs importés', \count($idMap)));

        return $idMap;
    }

    private function importTeams(SymfonyStyle $io, array $playerIdMap): array
    {
        $io->section('Import des équipes');

        $teams = $this->connection->fetchAllAssociative(
            'SELECT e.id, e.nom as tag, e.nc as full_name
             FROM ' . self::LEGACY_DB . '.equipe e
             WHERE e.id > 0'
        );

        $teamIdMap = []; // legacy_id => new_id
        $seenTags = [];
        $now = date('Y-m-d H:i:s');

        // Pre-fetch last connected player per team for creator
        $teamCreators = $this->connection->fetchAllAssociative(
            'SELECT j.equipe, j.id as player_id
             FROM ' . self::LEGACY_DB . '.joueur j
             INNER JOIN (
                 SELECT equipe, MAX(date_login) as max_login
                 FROM ' . self::LEGACY_DB . ".joueur
                 WHERE equipe > 0 AND actif = 'Oui'
                 GROUP BY equipe
             ) latest ON j.equipe = latest.equipe AND j.date_login = latest.max_login
             WHERE j.actif = 'Oui'"
        );
        $creatorMap = [];

        foreach ($teamCreators as $tc) {
            $creatorMap[(int) $tc['equipe']] = (int) $tc['player_id'];
        }

        foreach ($teams as $t) {
            $legacyId = (int) $t['id'];
            $tag = mb_substr(trim($t['tag']), 0, 10);
            $fullName = mb_substr(trim($t['full_name']), 0, 50);

            if ($tag === '') {
                continue;
            }

            if ($tag === '0') {
                continue;
            }

            if (isset($seenTags[$tag])) {
                continue;
            }

            // Find creator - need a valid player
            $creatorLegacyId = $creatorMap[$legacyId] ?? null;

            if ($creatorLegacyId === null) {
                continue;
            }

            if (!isset($playerIdMap[$creatorLegacyId])) {
                continue;
            }
            $creatorNewId = $playerIdMap[$creatorLegacyId];

            $seenTags[$tag] = true;

            $this->connection->insert('team', [
                'tag' => $tag,
                'full_name' => $fullName ?: $tag,
                'creator_id' => $creatorNewId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $teamIdMap[$legacyId] = (int) $this->connection->lastInsertId();
        }

        $io->info(\sprintf('  %d équipes importées', \count($teamIdMap)));

        // Create memberships
        $memberCount = 0;
        $activePlayers = $this->connection->fetchAllAssociative(
            'SELECT id, equipe FROM ' . self::LEGACY_DB . ".joueur
             WHERE equipe > 0 AND actif = 'Oui'"
        );

        foreach ($activePlayers as $ap) {
            $playerNewId = $playerIdMap[(int) $ap['id']] ?? null;
            $teamNewId = $teamIdMap[(int) $ap['equipe']] ?? null;

            if ($playerNewId && $teamNewId) {
                $this->connection->insert('team_membership', [
                    'player_id' => $playerNewId,
                    'team_id' => $teamNewId,
                    'joined_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                ++$memberCount;
            }
        }

        $io->info(\sprintf('  %d memberships créés', $memberCount));

        return $teamIdMap;
    }

    private function createSeason(SymfonyStyle $io): int
    {
        $io->section('Création de la saison');

        $champ = $this->connection->fetchAssociative(
            'SELECT * FROM ' . self::LEGACY_DB . '.championnat WHERE id = ?',
            [self::CHAMP_ID]
        );

        $now = date('Y-m-d H:i:s');

        $this->connection->insert('season', [
            'name' => $champ['nom_saison'],
            'slug' => 'mlc-reborn',
            'description' => 'Saison MLC Reborn - migrée depuis la version legacy',
            'start_date' => $champ['debut_inscription'],
            'end_date' => $champ['d'],
            'is_active' => 1,
            'min_players_for_team_ranking' => 4,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $seasonId = (int) $this->connection->lastInsertId();
        $io->info(\sprintf('  Saison "%s" créée (id=%d)', $champ['nom_saison'], $seasonId));

        return $seasonId;
    }

    private function createRound(SymfonyStyle $io, int $seasonId): int
    {
        $io->section('Création de la manche');

        $champ = $this->connection->fetchAssociative(
            'SELECT * FROM ' . self::LEGACY_DB . '.championnat WHERE id = ?',
            [self::CHAMP_ID]
        );

        $now = date('Y-m-d H:i:s');

        $this->connection->insert('round', [
            'season_id' => $seasonId,
            'number' => (int) $champ['num'],
            'name' => $champ['nom'],
            'is_active' => 0,
            'qualify_to_final_count' => (int) $champ['nbf'],
            'qualify_to_semi_count' => (int) $champ['nbd'],
            'qualify_from_semi_count' => (int) ((int) $champ['semi_to_final'] / 2),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $roundId = (int) $this->connection->lastInsertId();
        $io->info(\sprintf('  Manche "%s" créée (id=%d)', $champ['nom'], $roundId));

        return $roundId;
    }

    private function createPhases(SymfonyStyle $io, int $roundId): array
    {
        $io->section('Création des phases');

        $champ = $this->connection->fetchAssociative(
            'SELECT * FROM ' . self::LEGACY_DB . '.championnat WHERE id = ?',
            [self::CHAMP_ID]
        );

        $now = date('Y-m-d H:i:s');
        $phaseIds = [];

        // Registration phase
        $this->connection->insert('phase', [
            'round_id' => $roundId,
            'type' => 'registration',
            'start_at' => $champ['debut_inscription'],
            'end_at' => $champ['fin_inscription'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $phaseIds['registration'] = (int) $this->connection->lastInsertId();

        // Qualification phase
        $this->connection->insert('phase', [
            'round_id' => $roundId,
            'type' => 'qualification',
            'start_at' => $champ['debut_qualif'],
            'end_at' => $champ['fin_qualif'],
            'laps' => (int) $champ['nbtq'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $phaseIds['qualification'] = (int) $this->connection->lastInsertId();

        // Semi-Final group 1 (Saturday)
        $this->connection->insert('phase', [
            'round_id' => $roundId,
            'type' => 'semi_final',
            'start_at' => $champ['course1a'],
            'laps' => (int) $champ['nbtd'],
            'group_number' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $phaseIds['semi_final_1'] = (int) $this->connection->lastInsertId();

        // Semi-Final group 2 (Sunday)
        $this->connection->insert('phase', [
            'round_id' => $roundId,
            'type' => 'semi_final',
            'start_at' => $champ['course1b'],
            'laps' => (int) $champ['nbtd'],
            'group_number' => 2,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $phaseIds['semi_final_2'] = (int) $this->connection->lastInsertId();

        // Final
        $this->connection->insert('phase', [
            'round_id' => $roundId,
            'type' => 'final',
            'start_at' => $champ['d'],
            'laps' => (int) $champ['nbtf'],
            'group_number' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $phaseIds['final'] = (int) $this->connection->lastInsertId();

        $io->info(\sprintf('  %d phases créées', \count($phaseIds)));

        return $phaseIds;
    }

    private function createRegistrations(SymfonyStyle $io, int $roundId, array $playerIdMap, array $teamIdMap): array
    {
        $io->section('Création des inscriptions');

        // Get inscrits (serialized array of legacy IDs)
        $champ = $this->connection->fetchAssociative(
            'SELECT inscrits FROM ' . self::LEGACY_DB . '.championnat WHERE id = ?',
            [self::CHAMP_ID]
        );
        $inscrits = unserialize($champ['inscrits']);

        // Get dispo for each player
        $dispos = $this->connection->fetchAllKeyValue(
            'SELECT id, dispo FROM ' . self::LEGACY_DB . '.joueur WHERE id_champ = ?',
            [self::CHAMP_ID]
        );

        // Get team for each player
        $equipes = $this->connection->fetchAllKeyValue(
            'SELECT id, equipe FROM ' . self::LEGACY_DB . '.joueur WHERE id_champ = ?',
            [self::CHAMP_ID]
        );

        $registrationMap = []; // legacy_player_id => new_registration_id
        $count = 0;

        foreach ($inscrits as $legacyPlayerId) {
            $legacyPlayerId = (int) $legacyPlayerId;
            $newPlayerId = $playerIdMap[$legacyPlayerId] ?? null;

            if (!$newPlayerId) {
                continue;
            }

            $dispo = $dispos[$legacyPlayerId] ?? '';
            $legacyTeamId = (int) ($equipes[$legacyPlayerId] ?? 0);
            $newTeamId = $teamIdMap[$legacyTeamId] ?? null;

            // Map dispo: s=samedi(semi1), d=dimanche(semi2), sd=both, fd=finale only, f=forfait
            $semi1 = str_contains($dispo, 's');
            $semi2 = str_contains($dispo, 'd') && $dispo !== 'fd';
            $final = $dispo !== 'f'; // f=forfait, everyone else available for final

            $this->connection->insert('round_registration', [
                'round_id' => $roundId,
                'player_id' => $newPlayerId,
                'team_id' => $newTeamId,
                'registered_at' => date('Y-m-d H:i:s'),
                'available_semi_final1' => $semi1 ? 1 : 0,
                'available_semi_final2' => $semi2 ? 1 : 0,
                'available_final' => $final ? 1 : 0,
            ]);

            $registrationMap[$legacyPlayerId] = (int) $this->connection->lastInsertId();
            ++$count;
        }

        $io->info(\sprintf('  %d inscriptions créées', $count));

        return $registrationMap;
    }

    private function importMaps(SymfonyStyle $io, int $roundId): array
    {
        $io->section('Import des maps');

        $maps = $this->connection->fetchAllAssociative(
            'SELECT id, nom, uid, auteur, mt_lap, is_finale
             FROM ' . self::LEGACY_DB . '.map
             WHERE id_champ = ?
             ORDER BY is_finale ASC, id ASC',
            [self::CHAMP_ID]
        );

        $mapIdMap = []; // legacy_map_id => new_map_id
        $now = date('Y-m-d H:i:s');

        foreach ($maps as $m) {
            $this->connection->insert('round_map', [
                'round_id' => $roundId,
                'name' => $m['nom'],
                'uid' => $m['uid'],
                'author' => $m['auteur'],
                'author_time' => ((int) $m['mt_lap'] > 0) ? (int) $m['mt_lap'] : null,
                'is_surprise' => (int) $m['is_finale'] !== 0 ? 1 : 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $mapIdMap[(int) $m['id']] = (int) $this->connection->lastInsertId();
        }

        $io->info(\sprintf('  %d maps importées', \count($mapIdMap)));

        return $mapIdMap;
    }

    private function importMapRecords(SymfonyStyle $io, int $roundId): array
    {
        $io->section('Import des records');

        // Get map UIDs for this round (non-finale maps)
        $mapUids = [];
        $maps = $this->connection->fetchAllAssociative(
            'SELECT id, uid FROM ' . self::LEGACY_DB . '.map WHERE id_champ = ?',
            [self::CHAMP_ID]
        );

        foreach ($maps as $m) {
            $mapUids[(int) $m['id']] = $m['uid'];
        }

        // Get all qualification map UIDs (non-surprise)
        $qualifMapUids = $this->connection->fetchFirstColumn(
            'SELECT uid FROM ' . self::LEGACY_DB . '.map WHERE id_champ = ? AND is_finale = 0',
            [self::CHAMP_ID]
        );

        $now = date('Y-m-d H:i:s');
        $seenRecords = [];

        // Build pseudo map from joueur: use pseudoTMN if it has TM codes, otherwise fallback
        $pseudoTMNMap = [];
        $nicknameMap = $this->connection->fetchAllKeyValue(
            'SELECT LOWER(login), nickname FROM ' . self::LEGACY_DB . '.players'
        );
        $joueurRows = $this->connection->fetchAllAssociative(
            'SELECT login, pseudo, pseudoTMN FROM ' . self::LEGACY_DB . ".joueur WHERE actif = 'Oui'"
        );

        foreach ($joueurRows as $row) {
            $key = mb_strtolower($row['login']);
            $pseudoTMN = $row['pseudoTMN'] ?? '';
            $pseudoTMNMap[$key] = str_contains($pseudoTMN, '$') ? $pseudoTMN : ($nicknameMap[$key] ?? $row['pseudo']);
        }

        // TM server login → MLC login mapping for players whose TM login differs from MLC login
        $tmToMlcLogin = [
            'jashounet' => 'jashugan01',
            'ne-' => '-ne',
            'matador33' => 'marswann',
            'domi_7' => 'Domi_5',
            'totoman' => 'totoman1',
            '4lturbo' => 'yuggoth',
        ];
        // Keep only lowercase keys
        $tmToMlcLoginNorm = [];

        foreach ($tmToMlcLogin as $tm => $mlc) {
            $tmToMlcLoginNorm[mb_strtolower($tm)] = $mlc;
        }
        $tmToMlcLogin = $tmToMlcLoginNorm;

        $io->info(\sprintf('  %d mapping(s) TM->MLC: %s', \count($tmToMlcLogin), implode(', ', array_map(fn ($tm, $mlc): string => $tm . ' -> ' . $mlc, array_keys($tmToMlcLogin), array_values($tmToMlcLogin)))));

        // --- Import 5-lap records from live server 'results' table ---
        // These are used by RoundRankingService for qualification ranking
        $count5 = 0;
        $fiveLapRecords = $this->connection->fetchAllAssociative(
            'SELECT r.mapid, p.login, p.nickname, MIN(r.time) as best_time
             FROM ' . self::LEGACY_DB . '.results r
             JOIN ' . self::LEGACY_DB . '.players p ON r.playerid = p.playerid
             WHERE r.mapid IN (?) AND r.nblaps = 5 AND r.time > 0
             GROUP BY r.mapid, p.login, p.nickname',
            [$qualifMapUids],
            [ArrayParameterType::STRING]
        );

        foreach ($fiveLapRecords as $r) {
            // Remap TM server login to MLC login if needed
            $login = $tmToMlcLoginNorm[mb_strtolower($r['login'])] ?? $r['login'];
            $key = $r['mapid'] . '-' . $login . '-5-3-' . $roundId;

            if (isset($seenRecords[$key])) {
                continue;
            }
            $seenRecords[$key] = true;

            // Prefer pseudoTMN from joueur, fallback to players.nickname
            $pseudo = $pseudoTMNMap[mb_strtolower($login)] ?? $r['nickname'];

            $this->connection->insert('map_record', [
                'map_uid' => $r['mapid'],
                'player_login' => $login,
                'player' => mb_substr($pseudo, 0, 100),
                'laps' => 5,
                'time' => (int) $r['best_time'],
                'game_mode' => 3, // Laps
                'round_id' => $roundId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            ++$count5;
        }
        $io->info(\sprintf('  %d records 5 tours importés (qualification)', $count5));

        // --- Import 1-lap records (all maps including finale/surprise) ---
        // These are used for "best lap" bonus in RoundRankingService
        $allMapUids = array_values($mapUids);
        $count1 = 0;
        $oneLapRecords = $this->connection->fetchAllAssociative(
            'SELECT r.mapid, p.login, p.nickname, MIN(r.time) as best_time
             FROM ' . self::LEGACY_DB . '.results r
             JOIN ' . self::LEGACY_DB . '.players p ON r.playerid = p.playerid
             WHERE r.mapid IN (?) AND r.nblaps = 1 AND r.time > 0
             GROUP BY r.mapid, p.login, p.nickname',
            [$allMapUids],
            [ArrayParameterType::STRING]
        );

        foreach ($oneLapRecords as $r) {
            $login = $tmToMlcLoginNorm[mb_strtolower($r['login'])] ?? $r['login'];
            $key = $r['mapid'] . '-' . $login . '-1-3-' . $roundId;

            if (isset($seenRecords[$key])) {
                continue;
            }
            $seenRecords[$key] = true;

            $pseudo = $pseudoTMNMap[mb_strtolower($login)] ?? $r['nickname'];

            $this->connection->insert('map_record', [
                'map_uid' => $r['mapid'],
                'player_login' => $login,
                'player' => mb_substr($pseudo, 0, 100),
                'laps' => 1,
                'time' => (int) $r['best_time'],
                'game_mode' => 3, // Laps
                'round_id' => $roundId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            ++$count1;
        }
        $io->info(\sprintf('  %d records 1 tour importés (best lap)', $count1));

        // --- Import 10-lap records from course2 (final phase) ---
        $count10 = 0;
        $finalRecords = $this->connection->fetchAllAssociative(
            'SELECT DISTINCT c2.id_map, j.login, c2.temps
             FROM ' . self::LEGACY_DB . '.course2 c2
             JOIN ' . self::LEGACY_DB . '.joueur j ON c2.id_joueur = j.id
             WHERE c2.id_champ = ? AND c2.temps > 0',
            [self::CHAMP_ID]
        );

        foreach ($finalRecords as $r) {
            $legacyMapId = (int) $r['id_map'];
            $mapUid = $mapUids[$legacyMapId] ?? null;

            if ($mapUid === null) {
                continue;
            }

            $login = $tmToMlcLoginNorm[mb_strtolower($r['login'])] ?? $r['login'];
            $key = $mapUid . '-' . $login . '-10-3-' . $roundId;

            if (isset($seenRecords[$key])) {
                continue;
            }
            $seenRecords[$key] = true;

            $pseudo = $pseudoTMNMap[mb_strtolower($login)] ?? $r['login'];

            $this->connection->insert('map_record', [
                'map_uid' => $mapUid,
                'player_login' => $login,
                'player' => mb_substr($pseudo, 0, 100),
                'laps' => 10,
                'time' => (int) $r['temps'],
                'game_mode' => 3, // Laps
                'round_id' => $roundId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            ++$count10;
        }
        $io->info(\sprintf('  %d records 10 tours importés (finale)', $count10));

        return $mapUids;
    }

    private function importPhaseResults(SymfonyStyle $io, array $phaseIds, array $playerIdMap, array $registrationMap): void
    {
        $io->section('Import des résultats de phases');

        $now = date('Y-m-d H:i:s');

        // Get legacy player login => id mapping
        $this->connection->fetchAllKeyValue(
            'SELECT login, id FROM ' . self::LEGACY_DB . ".joueur WHERE actif = 'Oui'"
        );

        // Get qualif_final, qualif_finale_demi from championnat to know who qualified where
        $champ = $this->connection->fetchAssociative(
            'SELECT qualif_final, qualif_finale_demi, finale, semi_to_final FROM ' . self::LEGACY_DB . '.championnat WHERE id = ?',
            [self::CHAMP_ID]
        );
        $qualifFinal = unserialize($champ['qualif_final']);
        array_map(fn (array $e): int => (int) $e['id'], $qualifFinal);
        $qualifDemiToFinal = unserialize($champ['qualif_finale_demi']);
        array_map(fn (array $e): int => (int) $e['id'], $qualifDemiToFinal);
        $finalistes = unserialize($champ['finale']);
        $finalisteIds = array_map(fn ($e): int => (int) $e, $finalistes);

        // --- Semi-final results from course1 ---
        // course1 ordered by id: first half = semi 1, second half = semi 2
        $course1 = $this->connection->fetchAllAssociative(
            'SELECT c.id, c.id_joueur, c.temps
             FROM ' . self::LEGACY_DB . '.course1 c
             WHERE c.id_champ = ?
             ORDER BY c.id ASC',
            [self::CHAMP_ID]
        );

        $qualifyFromSemi = (int) ((int) $champ['semi_to_final'] / 2);
        $halfCount = (int) ceil(\count($course1) / 2);

        // Split into two groups, keep original id order
        $semiGroups = [
            'semi_final_1' => \array_slice($course1, 0, $halfCount),
            'semi_final_2' => \array_slice($course1, $halfCount),
        ];

        $semiCount = 0;

        foreach ($semiGroups as $groupKey => $players) {
            $position = 1;

            foreach ($players as $c) {
                $legacyPlayerId = (int) $c['id_joueur'];
                $newPlayerId = $playerIdMap[$legacyPlayerId] ?? null;
                $regId = $registrationMap[$legacyPlayerId] ?? null;

                if (!$newPlayerId) {
                    continue;
                }

                if (!$regId) {
                    continue;
                }

                $isQualified = $position <= $qualifyFromSemi;

                $this->connection->insert('phase_result', [
                    'phase_id' => $phaseIds[$groupKey],
                    'player_id' => $newPlayerId,
                    'registration_id' => $regId,
                    'time' => (int) $c['temps'],
                    'position' => $position,
                    'is_qualified' => $isQualified ? 1 : 0,
                    'qualified_to' => $isQualified ? 'final' : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                ++$position;
                ++$semiCount;
            }
        }
        $io->info(\sprintf('  %d résultats demi-finales importés', $semiCount));

        // --- Populate phase.players for semi-finals ---
        // qualif_demi contains IDs of all players qualified for semis
        $qualifDemiRaw = $this->connection->fetchOne(
            'SELECT qualif_demi FROM ' . self::LEGACY_DB . '.championnat WHERE id = ?',
            [self::CHAMP_ID]
        );
        $qualifDemiIds = array_unique(unserialize($qualifDemiRaw));

        // Get availability for each player to assign to correct semi group
        $dispos = $this->connection->fetchAllKeyValue(
            'SELECT id, dispo FROM ' . self::LEGACY_DB . '.joueur WHERE id_champ = ?',
            [self::CHAMP_ID]
        );

        // Build login mapping: legacy ID → MLC login
        $idToLegacyLogin = $this->connection->fetchAllKeyValue(
            'SELECT id, login FROM ' . self::LEGACY_DB . ".joueur WHERE actif = 'Oui'"
        );
        $tmToMlcLoginSemi = [
            'jashounet' => 'jashugan01',
            'ne-' => '-ne',
            'matador33' => 'marswann',
            'domi_7' => 'Domi_5',
            'totoman' => 'totoman1',
            '4lturbo' => 'yuggoth',
        ];

        $semi1Players = [];
        $semi2Players = [];

        foreach ($qualifDemiIds as $legacyId) {
            $legacyId = (int) $legacyId;
            $legacyLogin = $idToLegacyLogin[$legacyId] ?? null;

            if (!$legacyLogin) {
                continue;
            }
            $mlcLogin = $tmToMlcLoginSemi[mb_strtolower($legacyLogin)] ?? $legacyLogin;
            $dispo = $dispos[$legacyId] ?? '';

            // s=samedi(semi1), d=dimanche(semi2), sd=both
            $availSemi1 = str_contains($dispo, 's');
            $availSemi2 = str_contains($dispo, 'd') && $dispo !== 'fd';

            if ($availSemi1 && !$availSemi2) {
                $semi1Players[] = $mlcLogin;
            } elseif ($availSemi2 && !$availSemi1) {
                $semi2Players[] = $mlcLogin;
            } else {
                // Available for both or unclear: check which group they actually raced in
                $racedInSemi1 = false;

                foreach ($semiGroups['semi_final_1'] as $c) {
                    if ((int) $c['id_joueur'] === $legacyId) {
                        $racedInSemi1 = true;

                        break;
                    }
                }

                if ($racedInSemi1) {
                    $semi1Players[] = $mlcLogin;
                } else {
                    $semi2Players[] = $mlcLogin;
                }
            }
        }

        $this->connection->update('phase', ['players' => json_encode($semi1Players)], ['id' => $phaseIds['semi_final_1']]);
        $this->connection->update('phase', ['players' => json_encode($semi2Players)], ['id' => $phaseIds['semi_final_2']]);

        $io->info(\sprintf('  Joueurs demi-finales: %d en D1, %d en D2', \count($semi1Players), \count($semi2Players)));

        // --- Populate phase.players for final ---
        $finalPlayerLogins = [];

        foreach ($finalisteIds as $legacyId) {
            $legacyLogin = $idToLegacyLogin[$legacyId] ?? null;

            if (!$legacyLogin) {
                continue;
            }
            $finalPlayerLogins[] = $tmToMlcLoginSemi[mb_strtolower($legacyLogin)] ?? $legacyLogin;
        }
        $this->connection->update('phase', ['players' => json_encode($finalPlayerLogins)], ['id' => $phaseIds['final']]);
        $io->info(\sprintf('  Joueurs finale: %d', \count($finalPlayerLogins)));

        // --- Final results from course2 ---
        $course2 = $this->connection->fetchAllAssociative(
            'SELECT c.id_map, c.id_joueur, c.place, c.temps
             FROM ' . self::LEGACY_DB . '.course2 c
             WHERE c.id_champ = ?
             ORDER BY c.id_map, c.place',
            [self::CHAMP_ID]
        );

        // course2 has per-map results for the final, but PhaseResult expects one entry per player
        // We need to aggregate: sum times across all maps for each player, rank by total
        $finalPlayerTimes = [];

        foreach ($course2 as $c) {
            $pid = (int) $c['id_joueur'];

            if (!isset($finalPlayerTimes[$pid])) {
                $finalPlayerTimes[$pid] = 0;
            }
            $finalPlayerTimes[$pid] += (int) $c['temps'];
        }
        // Sort by total time
        asort($finalPlayerTimes);

        $finalCount = 0;
        $position = 1;

        foreach ($finalPlayerTimes as $legacyPlayerId => $totalTime) {
            $newPlayerId = $playerIdMap[$legacyPlayerId] ?? null;
            $regId = $registrationMap[$legacyPlayerId] ?? null;

            if (!$newPlayerId) {
                continue;
            }

            if (!$regId) {
                continue;
            }

            $this->connection->insert('phase_result', [
                'phase_id' => $phaseIds['final'],
                'player_id' => $newPlayerId,
                'registration_id' => $regId,
                'time' => $totalTime,
                'position' => $position,
                'is_qualified' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            ++$position;
            ++$finalCount;
        }
        $io->info(\sprintf('  %d résultats finales importés', $finalCount));
    }

    private function importQualificationRanking(SymfonyStyle $io, array $phaseIds): void
    {
        $io->section('Import du classement qualification');

        // Get the final qualification srank (largest one)
        $srank = $this->connection->fetchOne(
            'SELECT sr FROM ' . self::LEGACY_DB . ".srank
             WHERE idc = ? AND phase = 'Q'
             ORDER BY LENGTH(sr) DESC LIMIT 1",
            [self::CHAMP_ID]
        );

        if (!$srank) {
            $io->warning('Pas de classement qualification trouvé');

            return;
        }

        $rankData = unserialize($srank);

        if (!\is_array($rankData)) {
            $io->warning('Impossible de désérialiser le classement qualification');

            return;
        }

        // Get login → pseudo mapping: pseudoTMN if has TM codes, otherwise players.nickname, fallback pseudo
        $nicknameMap = $this->connection->fetchAllKeyValue(
            'SELECT LOWER(login), nickname FROM ' . self::LEGACY_DB . '.players'
        );
        $pseudoMap = [];
        $joueurRows = $this->connection->fetchAllAssociative(
            'SELECT login, pseudo, pseudoTMN, dispo FROM ' . self::LEGACY_DB . '.joueur WHERE id_champ = ?',
            [self::CHAMP_ID]
        );
        $loginToDispo = [];

        foreach ($joueurRows as $row) {
            $key = mb_strtolower($row['login']);
            $pseudoTMN = $row['pseudoTMN'] ?? '';
            $pseudoMap[$key] = str_contains($pseudoTMN, '$') ? $pseudoTMN : ($nicknameMap[$key] ?? $row['pseudo']);
            $loginToDispo[$key] = $row['dispo'] ?? '';
        }

        // TM server login → MLC login mapping
        $tmToMlcLogin = [
            'jashounet' => 'jashugan01',
            'ne-' => '-ne',
            'matador33' => 'marswann',
            'domi_7' => 'Domi_5',
            'totoman' => 'totoman1',
            '4lturbo' => 'yuggoth',
        ];

        // Build ranking array in new format
        // rankData is sorted by NbMap DESC, Points DESC
        $ranking = [];
        $position = 1;

        foreach ($rankData as $login => $data) {
            // Remap TM login to MLC login
            $mlcLogin = $tmToMlcLogin[mb_strtolower($login)] ?? $login;
            $loginLower = mb_strtolower($mlcLogin);
            $pseudo = $pseudoMap[$loginLower] ?? $login;
            $dispo = $loginToDispo[$loginLower] ?? '';

            $ranking[] = [
                'position' => $position,
                'login' => $mlcLogin,
                'pseudo' => $pseudo,
                'points' => (int) $data['Points'],
                'bonus' => 0,
                'total' => (int) $data['Points'],
                'nbMaps' => (int) $data['NbMap'],
                'availableSemiFinal' => str_contains($dispo, 's') || ($dispo === 'd' || $dispo === 'sd'),
                'availableFinal' => $dispo !== 'f',
            ];
            ++$position;
        }

        $this->connection->update('phase', [
            'ranking' => json_encode($ranking, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
            'ranking_updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $phaseIds['qualification']]);

        $io->info(\sprintf('  Classement qualification importé (%d joueurs)', \count($ranking)));
    }

    private function importFinalRanking(SymfonyStyle $io, array $phaseIds): void
    {
        $io->section('Import du classement final');

        $srank = $this->connection->fetchOne(
            'SELECT sr FROM ' . self::LEGACY_DB . ".srank
             WHERE idc = ? AND phase = 'F'
             ORDER BY LENGTH(sr) DESC LIMIT 1",
            [self::CHAMP_ID]
        );

        if (!$srank) {
            $io->warning('Pas de classement final trouvé');

            return;
        }

        $rankData = unserialize($srank);

        if (!\is_array($rankData)) {
            $io->warning('Impossible de désérialiser le classement final');

            return;
        }

        // Get login → pseudo mapping: pseudoTMN if has TM codes, otherwise players.nickname, fallback pseudo
        $nicknameMap = $this->connection->fetchAllKeyValue(
            'SELECT LOWER(login), nickname FROM ' . self::LEGACY_DB . '.players'
        );
        $pseudoMap = [];
        $joueurRows = $this->connection->fetchAllAssociative(
            'SELECT login, pseudo, pseudoTMN FROM ' . self::LEGACY_DB . ".joueur WHERE actif = 'Oui'"
        );

        foreach ($joueurRows as $row) {
            $key = mb_strtolower($row['login']);
            $pseudoTMN = $row['pseudoTMN'] ?? '';
            $pseudoMap[$key] = str_contains($pseudoTMN, '$') ? $pseudoTMN : ($nicknameMap[$key] ?? $row['pseudo']);
        }

        // TM server login → MLC login mapping
        $tmToMlcLogin = [
            'jashounet' => 'jashugan01',
            'ne-' => '-ne',
            'matador33' => 'marswann',
            'domi_7' => 'Domi_5',
            'totoman' => 'totoman1',
            '4lturbo' => 'yuggoth',
        ];

        // Get legacy joueur id → login mapping
        $legacyIdToLogin = $this->connection->fetchAllKeyValue(
            'SELECT id, login FROM ' . self::LEGACY_DB . ".joueur WHERE actif = 'Oui'"
        );

        // Get per-map results from course2
        $course2 = $this->connection->fetchAllAssociative(
            'SELECT c.id_map, c.id_joueur, c.place, c.temps
             FROM ' . self::LEGACY_DB . '.course2 c
             WHERE c.id_champ = ?
             ORDER BY c.id_map, c.place',
            [self::CHAMP_ID]
        );

        // Get map names
        $mapIds = array_unique(array_column($course2, 'id_map'));
        $mapNames = [];

        if ($mapIds !== []) {
            $rows = $this->connection->fetchAllAssociative(
                'SELECT id, nom FROM ' . self::LEGACY_DB . '.map WHERE id IN (' . implode(',', $mapIds) . ')'
            );

            foreach ($rows as $row) {
                $mapNames[(int) $row['id']] = $row['nom'];
            }
        }

        // Build per-player per-map results: legacyLogin => [mapId => ['place' => x, 'temps' => y]]
        $playerMapResults = [];

        foreach ($course2 as $c) {
            $legacyId = (int) $c['id_joueur'];
            $legacyLogin = $legacyIdToLogin[$legacyId] ?? null;

            if (!$legacyLogin) {
                continue;
            }
            $mlcLogin = $tmToMlcLogin[mb_strtolower($legacyLogin)] ?? $legacyLogin;
            $loginKey = mb_strtolower($mlcLogin);
            $mapId = (int) $c['id_map'];

            if (!isset($playerMapResults[$loginKey])) {
                $playerMapResults[$loginKey] = [];
            }
            $playerMapResults[$loginKey][$mapId] = [
                'place' => (int) $c['place'],
                'temps' => (int) $c['temps'],
            ];
        }

        // Build ordered map list
        $orderedMaps = [];

        foreach ($mapIds as $mapId) {
            $orderedMaps[] = [
                'id' => (int) $mapId,
                'name' => $mapNames[(int) $mapId] ?? 'Map ' . $mapId,
            ];
        }

        // Get srank Q for qualification positions → BQ
        $srankQ = $this->connection->fetchOne(
            'SELECT sr FROM ' . self::LEGACY_DB . ".srank
             WHERE idc = ? AND phase = 'Q'
             ORDER BY LENGTH(sr) DESC LIMIT 1",
            [self::CHAMP_ID]
        );
        $qualRankData = unserialize($srankQ);
        $qualPositions = []; // login => qualif position
        $qPos = 1;

        foreach ($qualRankData as $login => $d) {
            $mlc = $tmToMlcLogin[mb_strtolower($login)] ?? $login;
            $qualPositions[mb_strtolower($mlc)] = $qPos++;
        }
        $totalQualPlayers = \count($qualRankData);

        // Get qualif_demi for BD (who qualified for semi-finals)
        $qualifDemiRaw = $this->connection->fetchOne(
            'SELECT qualif_demi FROM ' . self::LEGACY_DB . '.championnat WHERE id = ?',
            [self::CHAMP_ID]
        );
        $qualifDemiIds = array_unique(unserialize($qualifDemiRaw));
        $demiLogins = [];

        foreach ($qualifDemiIds as $id) {
            $login = $legacyIdToLogin[(int) $id] ?? null;

            if ($login) {
                $mlc = $tmToMlcLogin[mb_strtolower($login)] ?? $login;
                $demiLogins[mb_strtolower($mlc)] = true;
            }
        }

        // Players who played final (have course2 entries) → BF
        $finalLogins = [];

        foreach ($playerMapResults as $loginKey => $maps) {
            $finalLogins[$loginKey] = true;
        }

        // srank F points for finalists
        $srankFPoints = [];

        foreach ($rankData as $login => $data) {
            $mlc = $tmToMlcLogin[mb_strtolower($login)] ?? $login;
            $srankFPoints[mb_strtolower($mlc)] = (int) $data['Points'];
        }
        $allEntries = [];

        foreach ($qualRankData as $login => $qualData) {
            $mlcLogin = $tmToMlcLogin[mb_strtolower($login)] ?? $login;
            $loginKey = mb_strtolower($mlcLogin);
            $pseudo = $pseudoMap[$loginKey] ?? $login;

            $qualPos = $qualPositions[$loginKey] ?? $totalQualPlayers;
            $bq = $totalQualPlayers + 11 - $qualPos; // 65 - pos for 54 players + 11 maps
            $bd = isset($demiLogins[$loginKey]) ? 10 : 0;
            $bf = isset($finalLogins[$loginKey]) ? 20 : 0;

            $fPoints = $srankFPoints[$loginKey] ?? 0;
            $total = ($fPoints > 0) ? ($fPoints * 2 + $bq + $bd + $bf) : ($bq + $bd);

            // Per-map positions
            $maps = [];

            foreach ($mapIds as $mapId) {
                $mapId = (int) $mapId;

                if (isset($playerMapResults[$loginKey][$mapId])) {
                    $maps[$mapId] = $playerMapResults[$loginKey][$mapId];
                }
            }

            $allEntries[] = [
                'login' => $mlcLogin,
                'pseudo' => $pseudo,
                'bq' => $bq,
                'bd' => $bd,
                'bf' => $bf,
                'total' => $total,
                'maps' => $maps,
            ];
        }

        // Sort by total DESC
        usort($allEntries, fn (array $a, array $b): int => $b['total'] <=> $a['total']);

        // Assign positions (handle ties)
        $position = 1;

        foreach ($allEntries as $i => &$entry) {
            if ($i > 0 && $allEntries[$i - 1]['total'] === $entry['total']) {
                $entry['position'] = $allEntries[$i - 1]['position'];
            } else {
                $entry['position'] = $position;
            }
            ++$position;
        }
        unset($entry);

        $finalData = [
            'ranking' => $allEntries,
            'maps' => $orderedMaps,
        ];

        $this->connection->update('phase', [
            'ranking' => json_encode($finalData, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
            'ranking_updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $phaseIds['final']]);

        $io->info(\sprintf('  Classement final importé (%d joueurs, %d maps)', \count($allEntries), \count($orderedMaps)));
    }
}
