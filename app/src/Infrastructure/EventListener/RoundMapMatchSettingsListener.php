<?php

declare(strict_types=1);

namespace App\Infrastructure\EventListener;

use App\Application\Championship\Service\MatchSettingsGeneratorService;
use App\Domain\Championship\Entity\RoundMap;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

#[AsEntityListener(event: Events::postPersist, method: 'onMapChange', entity: RoundMap::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'onMapChange', entity: RoundMap::class)]
#[AsEntityListener(event: Events::postRemove, method: 'onMapChange', entity: RoundMap::class)]
class RoundMapMatchSettingsListener
{
    public function __construct(
        private readonly MatchSettingsGeneratorService $matchSettingsGenerator,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function onMapChange(RoundMap $map): void
    {
        $round = $map->getRound();
        if ($round === null) {
            return;
        }

        // Regenerate MatchSettings for all playable phases of this round
        foreach ($round->getPhases() as $phase) {
            if (!$phase->getType()?->isPlayable()) {
                continue;
            }

            try {
                $filePath = $this->matchSettingsGenerator->saveForPhase($phase);
                $this->logger?->info('MatchSettings regenerated', [
                    'phase_id' => $phase->getId(),
                    'phase_type' => $phase->getType()?->value,
                    'file' => $filePath,
                ]);
            } catch (\Exception $e) {
                $this->logger?->error('Failed to regenerate MatchSettings', [
                    'phase_id' => $phase->getId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
