<?php

declare(strict_types=1);

namespace App\Application\Championship\Service;

use App\Domain\Championship\Entity\Phase;
use App\Domain\Championship\Entity\PhaseType;
use App\Domain\Championship\Entity\Round;
use App\Domain\Championship\Entity\RoundMap;
use App\Domain\Championship\Entity\RoundRegistration;
use App\Domain\Championship\Repository\PhaseRepositoryInterface;
use App\Domain\Championship\Repository\RoundRegistrationRepositoryInterface;
use Psr\Log\LoggerInterface;

class QualificationClosingService
{
    public function __construct(
        private readonly RoundRegistrationRepositoryInterface $registrationRepository,
        private readonly PhaseRepositoryInterface $phaseRepository,
        private readonly MatchSettingsGeneratorService $matchSettingsGenerator,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Clôture la phase de qualification et répartit les joueurs dans les demi-finales.
     *
     * @return array<int, array<string>>
     */
    public function closeQualification(Phase $qualificationPhase): array
    {
        $round = $qualificationPhase->getRound();

        if (!$round instanceof Round) {
            throw new \RuntimeException('La phase n\'est pas associée à une manche.');
        }

        $ranking = $qualificationPhase->getRanking();

        if ($ranking === null) {
            throw new \RuntimeException("Le classement de la phase de qualification n'est pas disponible.");
        }

        // Récupérer les demi-finales actives (non supprimées)
        $activeSemiFinals = $this->getActiveSemiFinals($round);

        if ($activeSemiFinals === []) {
            throw new \RuntimeException('Aucune demi-finale active trouvée.');
        }

        // Nombre de qualifiés selon la config du round
        $qualifyToFinalCount = $round->getQualifyToFinalCount();
        $qualifyToSemiCount = $round->getQualifyToSemiCount();

        if ($qualifyToSemiCount === 0) {
            throw new \RuntimeException('Aucun joueur configuré pour les demi-finales (qualifyToSemiCount = 0).');
        }

        // Trier le ranking par position
        usort($ranking, fn (array $a, array $b): int => $a['position'] <=> $b['position']);

        // Récupérer les inscriptions pour connaître les disponibilités à jour
        // (le ranking stocké peut avoir des dispos obsolètes si elles ont été modifiées
        //  après le dernier rafraîchissement du classement).
        $registrations = $this->registrationRepository->findByRound($round);
        $registrationsByLogin = [];

        foreach ($registrations as $registration) {
            $registrationsByLogin[mb_strtolower($registration->getPlayer()->getLogin())] = $registration;
        }

        // Sélection des qualifiés en suivant la même règle que la page de phase
        // (templates/championship/round/phase.html.twig) : on descend le classement et on
        // remplit d'abord le quota finale (joueurs dispo finale, ayant joué toutes les
        // maps non-surprise), puis le quota demi (dispo finale ET au moins une demi).
        // Les forfaits / incomplets sont sautés, ce qui fait remonter les suivants.
        $totalMaps = 0;

        foreach ($round->getMaps() as $roundMap) {
            if (!$roundMap instanceof RoundMap || $roundMap->isSurprise()) {
                continue;
            }
            ++$totalMaps;
        }

        $this->logger->info('[CloseQualif] start', [
            'roundId' => $round->getId(),
            'phaseId' => $qualificationPhase->getId(),
            'qualifyToFinalCount' => $qualifyToFinalCount,
            'qualifyToSemiCount' => $qualifyToSemiCount,
            'totalMaps' => $totalMaps,
            'rankingSize' => \count($ranking),
            'registrationsCount' => \count($registrationsByLogin),
        ]);

        $qualifiedForSemi = [];
        $finalSlotsUsed = 0;
        $semiSlotsUsed = 0;

        foreach ($ranking as $entry) {
            $login = mb_strtolower((string) ($entry['login'] ?? ''));
            $position = $entry['position'] ?? null;
            $nbMaps = $entry['nbMaps'] ?? 0;

            if ($finalSlotsUsed >= $qualifyToFinalCount && $semiSlotsUsed >= $qualifyToSemiCount) {
                $this->logger->debug('[CloseQualif] quotas full, stop', [
                    'finalSlotsUsed' => $finalSlotsUsed,
                    'semiSlotsUsed' => $semiSlotsUsed,
                    'stoppedAtPosition' => $position,
                ]);

                break;
            }

            $registration = $registrationsByLogin[$login] ?? null;

            if (!$registration instanceof RoundRegistration) {
                $this->logger->warning('[CloseQualif] skip: no registration', [
                    'position' => $position,
                    'login' => $login,
                ]);

                continue;
            }

            if ($nbMaps < $totalMaps) {
                $this->logger->info('[CloseQualif] skip: incomplete maps', [
                    'position' => $position,
                    'login' => $login,
                    'nbMaps' => $nbMaps,
                    'totalMaps' => $totalMaps,
                ]);

                continue;
            }

            $availableFinal = $registration->isAvailableFinal();
            $availableSemi1 = $registration->isAvailableSemiFinal1();
            $availableSemi2 = $registration->isAvailableSemiFinal2();
            $availableSemi = $availableSemi1 || $availableSemi2;

            if (!$availableFinal) {
                $this->logger->info('[CloseQualif] skip: forfait (availableFinal=false)', [
                    'position' => $position,
                    'login' => $login,
                    'availableSemi1' => $availableSemi1,
                    'availableSemi2' => $availableSemi2,
                ]);

                continue;
            }

            if ($finalSlotsUsed < $qualifyToFinalCount) {
                ++$finalSlotsUsed;
                $this->logger->info('[CloseQualif] → finale slot', [
                    'position' => $position,
                    'login' => $login,
                    'finalSlotsUsed' => $finalSlotsUsed,
                    'finalQuota' => $qualifyToFinalCount,
                ]);

                continue;
            }

            if ($availableSemi && $semiSlotsUsed < $qualifyToSemiCount) {
                $qualifiedForSemi[] = $entry;
                ++$semiSlotsUsed;
                $this->logger->info('[CloseQualif] → semi pool', [
                    'position' => $position,
                    'login' => $login,
                    'semiSlotsUsed' => $semiSlotsUsed,
                    'semiQuota' => $qualifyToSemiCount,
                    'availableSemi1' => $availableSemi1,
                    'availableSemi2' => $availableSemi2,
                ]);

                continue;
            }

            $this->logger->info('[CloseQualif] skip: no semi dispo or semi quota full', [
                'position' => $position,
                'login' => $login,
                'availableSemi' => $availableSemi,
                'semiSlotsUsed' => $semiSlotsUsed,
                'semiQuota' => $qualifyToSemiCount,
            ]);
        }

        $this->logger->info('[CloseQualif] selection done', [
            'finalSlotsUsed' => $finalSlotsUsed,
            'semiSlotsUsed' => $semiSlotsUsed,
            'qualifiedForSemiLogins' => array_map(static fn (array $e): string => (string) ($e['login'] ?? ''), $qualifiedForSemi),
        ]);

        // Si une seule demi-finale, tous les joueurs y vont
        if (\count($activeSemiFinals) === 1) {
            $allPlayers = [];

            foreach ($qualifiedForSemi as $entry) {
                $allPlayers[] = $entry['login'];
            }

            $semiPhase = reset($activeSemiFinals);
            $groupNumber = $semiPhase->getGroupNumber() ?? 1;
            $semiPhase->setPlayers($allPlayers);
            $this->phaseRepository->save($semiPhase);

            // Générer le MatchSettings pour cette demi-finale
            $this->matchSettingsGenerator->saveForPhase($semiPhase);

            return [$groupNumber => $allPlayers];
        }

        // Deux demi-finales : répartition selon les disponibilités
        $onlySemi1 = [];
        $onlySemi2 = [];
        $bothAvailable = [];

        foreach ($qualifiedForSemi as $entry) {
            $login = $entry['login'];
            $registration = $registrationsByLogin[$login] ?? null;

            if ($registration === null) {
                continue;
            }

            $availSemi1 = $registration->isAvailableSemiFinal1();
            $availSemi2 = $registration->isAvailableSemiFinal2();

            if ($availSemi1 && $availSemi2) {
                $bothAvailable[] = $login;
            } elseif ($availSemi1) {
                $onlySemi1[] = $login;
            } elseif ($availSemi2) {
                $onlySemi2[] = $login;
            }
            // Si aucune dispo, le joueur n'est pas ajouté (cas rare)
        }

        // Répartition équitable
        $semi1Players = $onlySemi1;
        $semi2Players = $onlySemi2;

        // Mélanger aléatoirement ceux qui sont dispo pour les 2
        shuffle($bothAvailable);

        // Répartir ceux qui sont dispo pour les 2
        foreach ($bothAvailable as $login) {
            if (\count($semi1Players) <= \count($semi2Players)) {
                $semi1Players[] = $login;
            } else {
                $semi2Players[] = $login;
            }
        }

        $this->logger->info('[CloseQualif] split semis', [
            'semi1Count' => \count($semi1Players),
            'semi1Players' => $semi1Players,
            'semi2Count' => \count($semi2Players),
            'semi2Players' => $semi2Players,
            'onlySemi1Count' => \count($onlySemi1),
            'onlySemi2Count' => \count($onlySemi2),
            'bothAvailableCount' => \count($bothAvailable),
        ]);

        // Mettre à jour les phases demi-finales
        $this->updateSemiFinalPhase($round, 1, $semi1Players);
        $this->updateSemiFinalPhase($round, 2, $semi2Players);

        // Générer les MatchSettings pour les demi-finales
        foreach ($activeSemiFinals as $semiPhase) {
            $this->matchSettingsGenerator->saveForPhase($semiPhase);
        }

        return [
            1 => $semi1Players,
            2 => $semi2Players,
        ];
    }

    /**
     * @return Phase[]
     */
    private function getActiveSemiFinals(Round $round): array
    {
        $semiFinals = [];

        foreach ($round->getPhases() as $phase) {
            if ($phase->getType() === PhaseType::SemiFinal) {
                $semiFinals[] = $phase;
            }
        }

        return $semiFinals;
    }

    /**
     * @param array<string> $players
     */
    private function updateSemiFinalPhase(Round $round, int $groupNumber, array $players): void
    {
        foreach ($round->getPhases() as $phase) {
            if ($phase->getType() === PhaseType::SemiFinal && $phase->getGroupNumber() === $groupNumber) {
                $phase->setPlayers($players);
                $this->phaseRepository->save($phase);

                return;
            }
        }
    }
}
