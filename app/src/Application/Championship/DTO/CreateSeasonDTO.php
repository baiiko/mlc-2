<?php

declare(strict_types=1);

namespace App\Application\Championship\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateSeasonDTO
{
    #[Assert\NotBlank(message: 'validation.name_required')]
    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: 'validation.name_min_length',
        maxMessage: 'validation.name_max_length'
    )]
    public ?string $name = null;

    #[Assert\Length(max: 2000)]
    public ?string $description = null;

    #[Assert\NotNull(message: 'validation.start_date_required')]
    public ?\DateTimeImmutable $startDate = null;

    public ?\DateTimeImmutable $endDate = null;

    #[Assert\Range(min: 1, max: 10)]
    public int $minPlayersForTeamRanking = 4;
}
