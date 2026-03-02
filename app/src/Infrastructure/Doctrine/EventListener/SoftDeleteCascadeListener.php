<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\EventListener;

use App\Domain\Championship\Entity\Round;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::onFlush)]
class SoftDeleteCascadeListener
{
    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof Round) {
                continue;
            }

            $changeSet = $uow->getEntityChangeSet($entity);

            // Check if deletedAt was just set (soft delete happening)
            if (!isset($changeSet['deletedAt'])) {
                continue;
            }

            $oldValue = $changeSet['deletedAt'][0];
            $newValue = $changeSet['deletedAt'][1];

            // Only cascade if deletedAt is being set (was null, now has value)
            if ($oldValue !== null || $newValue === null) {
                continue;
            }

            $this->cascadeSoftDelete($em, $entity, $newValue);
        }
    }

    private function cascadeSoftDelete(EntityManagerInterface $em, Round $round, \DateTimeInterface $deletedAt): void
    {
        $uow = $em->getUnitOfWork();

        // Soft delete all phases
        foreach ($round->getPhases() as $phase) {
            if ($phase->getDeletedAt() === null) {
                $phase->setDeletedAt(\DateTimeImmutable::createFromInterface($deletedAt));
                $em->persist($phase);
                $uow->recomputeSingleEntityChangeSet($em->getClassMetadata($phase::class), $phase);
            }
        }

        // Soft delete all registrations
        foreach ($round->getRegistrations() as $registration) {
            if ($registration->getDeletedAt() === null) {
                $registration->setDeletedAt(\DateTimeImmutable::createFromInterface($deletedAt));
                $em->persist($registration);
                $uow->recomputeSingleEntityChangeSet($em->getClassMetadata($registration::class), $registration);
            }
        }

        // Soft delete all maps
        foreach ($round->getMaps() as $map) {
            if ($map->getDeletedAt() === null) {
                $map->setDeletedAt(\DateTimeImmutable::createFromInterface($deletedAt));
                $em->persist($map);
                $uow->recomputeSingleEntityChangeSet($em->getClassMetadata($map::class), $map);
            }
        }
    }
}
