<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Admin\EventSubscriber;

use App\Application\Championship\Service\RoundPhaseGeneratorService;
use App\Domain\Championship\Entity\Round;
use App\Domain\Championship\Entity\Phase;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RoundPhaseSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RoundPhaseGeneratorService $phaseGenerator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityPersistedEvent::class => 'onBeforeRoundPersisted',
        ];
    }

    public function onBeforeRoundPersisted(BeforeEntityPersistedEvent $event): void
    {
        $entity = $event->getEntityInstance();

        if (!$entity instanceof Round) {
            return;
        }

        $startDate = $entity->getStartDate();
        if ($startDate === null) {
            return;
        }

        $season = $entity->getSeason();
        if ($season === null) {
            return;
        }

        // Generate phases to get the date range
        $phases = $this->phaseGenerator->generatePhases($entity, $startDate);

        // Get the date range of the new round
        $newRoundStart = $this->getEarliestPhaseStart($phases);
        $newRoundEnd = $this->getLatestPhaseStart($phases);

        // Check for overlapping rounds in the same season
        $existingRounds = $this->entityManager->getRepository(Round::class)->findBy([
            'season' => $season,
        ]);

        foreach ($existingRounds as $existingRound) {
            if ($existingRound->getId() === $entity->getId()) {
                continue;
            }

            $existingPhases = $existingRound->getPhases();
            if ($existingPhases->isEmpty()) {
                continue;
            }

            $existingStart = $this->getEarliestPhaseStartFromCollection($existingPhases);
            $existingEnd = $this->getLatestPhaseStartFromCollection($existingPhases);

            if ($existingStart === null || $existingEnd === null) {
                continue;
            }

            // Check for overlap
            if ($this->datesOverlap($newRoundStart, $newRoundEnd, $existingStart, $existingEnd)) {
                throw new \RuntimeException(sprintf(
                    'Cette manche chevauche la manche "%s" (du %s au %s)',
                    $existingRound->getName(),
                    $existingStart->format('d/m/Y'),
                    $existingEnd->format('d/m/Y')
                ));
            }
        }

        // No overlap, add phases
        foreach ($phases as $phase) {
            $entity->addPhase($phase);
            $this->entityManager->persist($phase);
        }
    }

    /**
     * @param Phase[] $phases
     */
    private function getEarliestPhaseStart(array $phases): ?\DateTimeImmutable
    {
        $earliest = null;
        foreach ($phases as $phase) {
            $start = $phase->getStartAt();
            if ($start !== null && ($earliest === null || $start < $earliest)) {
                $earliest = $start;
            }
        }
        return $earliest;
    }

    /**
     * @param Phase[] $phases
     */
    private function getLatestPhaseStart(array $phases): ?\DateTimeImmutable
    {
        $latest = null;
        foreach ($phases as $phase) {
            $start = $phase->getStartAt();
            if ($start !== null && ($latest === null || $start > $latest)) {
                $latest = $start;
            }
        }
        return $latest;
    }

    /**
     * @param iterable<Phase> $phases
     */
    private function getEarliestPhaseStartFromCollection(iterable $phases): ?\DateTimeImmutable
    {
        $earliest = null;
        foreach ($phases as $phase) {
            $start = $phase->getStartAt();
            if ($start !== null && ($earliest === null || $start < $earliest)) {
                $earliest = $start;
            }
        }
        return $earliest;
    }

    /**
     * @param iterable<Phase> $phases
     */
    private function getLatestPhaseStartFromCollection(iterable $phases): ?\DateTimeImmutable
    {
        $latest = null;
        foreach ($phases as $phase) {
            $start = $phase->getStartAt();
            if ($start !== null && ($latest === null || $start > $latest)) {
                $latest = $start;
            }
        }
        return $latest;
    }

    private function datesOverlap(
        ?\DateTimeImmutable $start1,
        ?\DateTimeImmutable $end1,
        \DateTimeImmutable $start2,
        \DateTimeImmutable $end2
    ): bool {
        if ($start1 === null || $end1 === null) {
            return false;
        }

        // Two ranges overlap if one starts before the other ends
        return $start1 <= $end2 && $end1 >= $start2;
    }
}
