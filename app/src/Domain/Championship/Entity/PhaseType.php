<?php

declare(strict_types=1);

namespace App\Domain\Championship\Entity;

enum PhaseType: string
{
    case Registration = 'registration';
    case Qualification = 'qualification';
    case SemiFinal = 'semi_final';
    case Final = 'final';

    public function getLabel(): string
    {
        return match ($this) {
            self::Registration => 'Inscriptions',
            self::Qualification => 'Qualifications',
            self::SemiFinal => 'Demi-finale',
            self::Final => 'Finale',
        };
    }

    public function getTranslationKey(): string
    {
        return match ($this) {
            self::Registration => 'championship.phase.registration',
            self::Qualification => 'championship.phase.qualification',
            self::SemiFinal => 'championship.phase.semi_final',
            self::Final => 'championship.phase.final',
        };
    }

    public function isPlayable(): bool
    {
        return $this !== self::Registration;
    }
}
