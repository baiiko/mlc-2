<?php

declare(strict_types=1);

namespace App\Application\Championship\Service;

use App\Domain\Championship\Entity\Phase;
use App\Domain\Championship\Entity\PhaseType;
use App\Domain\Championship\Entity\Round;
use App\Domain\Championship\Repository\PhaseRepositoryInterface;
use App\Domain\Championship\Repository\RoundRegistrationRepositoryInterface;

class QualificationClosingService
{
    public function __construct(
        private readonly RoundRegistrationRepositoryInterface $registrationRepository,
        private readonly PhaseRepositoryInterface $phaseRepository,
        private readonly MatchSettingsGeneratorService $matchSettingsGenerator,
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
        if ($round === null) {
            throw new \RuntimeException('La phase n\'est pas associée à une manche.');
        }

        $ranking = $qualificationPhase->getRanking();
        if ($ranking === null) {
            throw new \RuntimeException('Le classement de la phase de qualification n\'est pas disponible.');
        }

        // Récupérer les demi-finales actives (non supprimées)
        $activeSemiFinals = $this->getActiveSemiFinals($round);
        if (empty($activeSemiFinals)) {
            throw new \RuntimeException('Aucune demi-finale active trouvée.');
        }

        // Nombre de qualifiés selon la config du round
        $qualifyToFinalCount = $round->getQualifyToFinalCount();
        $qualifyToSemiCount = $round->getQualifyToSemiCount();

        if ($qualifyToSemiCount === 0) {
            throw new \RuntimeException('Aucun joueur configuré pour les demi-finales (qualifyToSemiCount = 0).');
        }

        // Trier le ranking par position
        usort($ranking, fn (array $a, array $b) => $a['position'] <=> $b['position']);

        // Joueurs qualifiés pour les demi-finales = positions (qualifyToFinalCount + 1) à (qualifyToFinalCount + qualifyToSemiCount)
        $startPosition = $qualifyToFinalCount + 1;
        $endPosition = $qualifyToFinalCount + $qualifyToSemiCount;

        $qualifiedForSemi = array_filter(
            $ranking,
            fn (array $entry) => $entry['position'] >= $startPosition && $entry['position'] <= $endPosition
        );

        // Récupérer les inscriptions pour connaître les disponibilités
        $registrations = $this->registrationRepository->findByRound($round);
        $registrationsByLogin = [];
        foreach ($registrations as $registration) {
            $registrationsByLogin[$registration->getPlayer()->getLogin()] = $registration;
        }

        // Si une seule demi-finale, tous les joueurs y vont
        if (count($activeSemiFinals) === 1) {
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
            if (count($semi1Players) <= count($semi2Players)) {
                $semi1Players[] = $login;
            } else {
                $semi2Players[] = $login;
            }
        }

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
