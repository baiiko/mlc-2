<?php

declare(strict_types=1);

namespace App\Application\Team\DTO;

use App\Domain\Team\Entity\Team;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueEntity(fields: ['tag'], entityClass: Team::class, message: 'validation.tag_exists')]
final class CreateTeamDTO
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
