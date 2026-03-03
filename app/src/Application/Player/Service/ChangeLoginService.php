<?php

declare(strict_types=1);

namespace App\Application\Player\Service;

use App\Domain\Championship\Repository\MapRecordRepositoryInterface;
use App\Domain\Championship\Repository\PhaseRepositoryInterface;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\Repository\PlayerRepositoryInterface;

final readonly class ChangeLoginService implements ChangeLoginServiceInterface
{
    public function __construct(
        private PlayerRepositoryInterface $playerRepository,
        private MapRecordRepositoryInterface $mapRecordRepository,
        private PhaseRepositoryInterface $phaseRepository,
    ) {
    }

    public function changeLogin(Player $player, string $newLogin): array
    {
        $oldLogin = $player->getLogin();

        if ($oldLogin === $newLogin) {
            return ['success' => true, 'updatedRecords' => 0, 'updatedPhases' => 0, 'error' => null];
        }

        $existing = $this->playerRepository->findByLogin($newLogin);

        if ($existing instanceof Player) {
            return ['success' => false, 'updatedRecords' => 0, 'updatedPhases' => 0, 'error' => 'Ce login est déjà utilisé.'];
        }

        $player->setLogin($newLogin);
        $updatedRecords = $this->mapRecordRepository->updatePlayerLogin($oldLogin, $newLogin);
        $updatedPhases = $this->updatePhaseData($oldLogin, $newLogin);

        $this->playerRepository->save($player);

        return [
            'success' => true,
            'updatedRecords' => $updatedRecords,
            'updatedPhases' => $updatedPhases,
            'error' => null,
        ];
    }

    private function updatePhaseData(string $oldLogin, string $newLogin): int
    {
        $phases = $this->phaseRepository->findWithLoginData();
        $updatedCount = 0;
        $oldLoginLower = mb_strtolower($oldLogin);

        foreach ($phases as $phase) {
            $modified = false;

            // Mise à jour du ranking JSON
            $ranking = $phase->getRanking();

            if ($ranking !== null && isset($ranking['ranking'])) {
                foreach ($ranking['ranking'] as &$entry) {
                    if (isset($entry['login']) && mb_strtolower($entry['login']) === $oldLoginLower) {
                        $entry['login'] = $newLogin;
                        $modified = true;
                    }
                }
                unset($entry);

                if ($modified) {
                    $phase->setRanking($ranking);
                }
            }

            // Mise à jour du tableau players JSON
            $players = $phase->getPlayers();

            if ($players !== null) {
                $key = array_search($oldLogin, array_map('mb_strtolower', $players));

                if ($key !== false) {
                    $players[$key] = $newLogin;
                    $phase->setPlayers($players);
                    $modified = true;
                }
            }

            if ($modified) {
                $this->phaseRepository->save($phase);
                ++$updatedCount;
            }
        }

        return $updatedCount;
    }
}
