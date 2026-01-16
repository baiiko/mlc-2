<?php

declare(strict_types=1);

namespace App\Application\Team\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateTeamDTO
{
    #[Assert\NotBlank(message: 'validation.tag_required')]
    #[Assert\Length(
        min: 2,
        max: 10,
        minMessage: 'validation.tag_min_length',
        maxMessage: 'validation.tag_max_length'
    )]
    public ?string $tag = null;

    #[Assert\NotBlank(message: 'validation.fullname_required')]
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: 'validation.fullname_min_length',
        maxMessage: 'validation.fullname_max_length'
    )]
    public ?string $fullName = null;
}
