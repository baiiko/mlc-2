<?php

declare(strict_types=1);

namespace App\Domain\Championship\Entity;

enum PhaseType: string
{
    case Registration = 'registration';
    case Qualification = 'qualification';
    case SemiFinal1 = 'semi_final_1';
    case SemiFinal2 = 'semi_final_2';
    case Final = 'final';

    public function getLabel(): string
    {
        return match ($this) {
            self::Registration => 'Inscriptions',
            self::Qualification => 'Qualifications',
            self::SemiFinal1 => 'Demi-finale 1',
            self::SemiFinal2 => 'Demi-finale 2',
            self::Final => 'Finale',
        };
    }

    public function getTranslationKey(): string
    {
        return match ($this) {
            self::Registration => 'championship.phase.registration',
            self::Qualification => 'championship.phase.qualification',
            self::SemiFinal1 => 'championship.phase.semi_final_1',
            self::SemiFinal2 => 'championship.phase.semi_final_2',
            self::Final => 'championship.phase.final',
        };
    }

    public function isPlayable(): bool
    {
        return $this !== self::Registration;
    }
}
