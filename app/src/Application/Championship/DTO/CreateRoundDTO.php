<?php

declare(strict_types=1);

namespace App\Application\Championship\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateRoundDTO
{
    #[Assert\NotBlank(message: 'validation.name_required')]
    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: 'validation.name_min_length',
        maxMessage: 'validation.name_max_length'
    )]
    public ?string $name = null;

    #[Assert\Length(max: 255)]
    public ?string $mapName = null;

    #[Assert\Length(max: 50)]
    public ?string $mapUid = null;

    #[Assert\NotNull(message: 'validation.registration_start_required')]
    public ?\DateTimeImmutable $registrationStartAt = null;

    #[Assert\NotNull(message: 'validation.registration_end_required')]
    #[Assert\GreaterThan(propertyPath: 'registrationStartAt', message: 'validation.registration_end_after_start')]
    public ?\DateTimeImmutable $registrationEndAt = null;
}
