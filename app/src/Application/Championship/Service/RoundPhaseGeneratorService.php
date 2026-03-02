<?php

declare(strict_types=1);

namespace App\Application\Championship\Service;

use App\Domain\Championship\Entity\Phase;
use App\Domain\Championship\Entity\PhaseType;
use App\Domain\Championship\Entity\Round;

class RoundPhaseGeneratorService
{
    /**
     * Generate all phases for a round based on a start date.
     * The start date is used to find the next Wednesday which becomes the reference point.
     *
     * Schedule (inscriptions and qualifications overlap):
     * - Registration: Wednesday 21:00 → Thursday 21:00 (+8 days)
     * - Qualification: Friday 21:00 (+2 days) → Friday 21:00 (+9 days)
     * - Semi-final 1: Saturday 21:00 (+10 days)
     * - Semi-final 2: Sunday 16:00 (+11 days)
     * - Final: Sunday 21:00 (+11 days)
     *
     * @return Phase[]
     */
    public function generatePhases(Round $round, \DateTimeInterface $startDate): array
    {
        $wednesday = $this->findNextWednesday($startDate);
        $phases = [];

        // Registration: Wednesday 21:00 → Thursday 21:00 (+8 days)
        $registrationStart = $this->createDateTimeOffset($wednesday, 0, 21, 0);
        $registrationEnd = $this->createDateTimeOffset($wednesday, 8, 21, 0);
        $phases[] = $this->createPhase($round, PhaseType::Registration, $registrationStart, $registrationEnd);

        // Qualification: Friday 21:00 (+2 days) → Friday 21:00 (+9 days)
        $qualificationStart = $this->createDateTimeOffset($wednesday, 2, 21, 0);
        $qualificationEnd = $this->createDateTimeOffset($wednesday, 9, 21, 0);
        $phases[] = $this->createPhase($round, PhaseType::Qualification, $qualificationStart, $qualificationEnd);

        // Semi-final 1: Saturday 21:00 (+10 days)
        $semiFinal1Start = $this->createDateTimeOffset($wednesday, 10, 21, 0);
        $phases[] = $this->createPhase($round, PhaseType::SemiFinal, $semiFinal1Start, null, 1);

        // Semi-final 2: Sunday 16:00 (+11 days)
        $semiFinal2Start = $this->createDateTimeOffset($wednesday, 11, 16, 0);
        $phases[] = $this->createPhase($round, PhaseType::SemiFinal, $semiFinal2Start, null, 2);

        // Final 1: Sunday 21:00 (+11 days)
        $finalStart = $this->createDateTimeOffset($wednesday, 11, 21, 0);
        $phases[] = $this->createPhase($round, PhaseType::Final, $finalStart, null, 1);

        // Final 2: Sunday 21:00 (+11 days)
        $phases[] = $this->createPhase($round, PhaseType::Final, $finalStart, null, 2);

        return $phases;
    }

    private function findNextWednesday(\DateTimeInterface $date): \DateTime
    {
        $dateTime = new \DateTime($date->format('Y-m-d'));
        $dayOfWeek = (int) $dateTime->format('N'); // 1 = Monday, 7 = Sunday

        // Wednesday is day 3
        if ($dayOfWeek <= 3) {
            // If before or on Wednesday, go to this week's Wednesday
            $daysToAdd = 3 - $dayOfWeek;
        } else {
            // If after Wednesday, go to next week's Wednesday
            $daysToAdd = 10 - $dayOfWeek;
        }

        if ($daysToAdd > 0) {
            $dateTime->modify(\sprintf('+%s days', $daysToAdd));
        }

        return $dateTime;
    }

    private function createDateTimeOffset(\DateTime $baseDate, int $daysOffset, int $hour, int $minute): \DateTimeImmutable
    {
        $date = clone $baseDate;

        if ($daysOffset > 0) {
            $date->modify(\sprintf('+%d days', $daysOffset));
        }
        $date->setTime($hour, $minute, 0);

        return \DateTimeImmutable::createFromMutable($date);
    }

    private function createPhase(
        Round $round,
        PhaseType $type,
        \DateTimeImmutable $startAt,
        ?\DateTimeImmutable $endAt = null,
        ?int $groupNumber = null,
    ): Phase {
        $phase = new Phase($round, $type, $startAt);

        if ($endAt instanceof \DateTimeImmutable) {
            $phase->setEndAt($endAt);
        }

        if ($groupNumber !== null) {
            $phase->setGroupNumber($groupNumber);
        }

        return $phase;
    }
}
