<?php

declare(strict_types=1);

namespace App\Application\Player\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateProfileDTO
{
    #[Assert\NotBlank(message: 'validation.pseudo_required')]
    #[Assert\Length(
        min: 2,
        max: 50,
        minMessage: 'validation.pseudo_min_length',
        maxMessage: 'validation.pseudo_max_length'
    )]
    public ?string $pseudo = null;

    #[Assert\NotBlank(message: 'validation.email_required')]
    #[Assert\Email(message: 'validation.email_invalid')]
    public ?string $email = null;

    public ?string $discord = null;

    public bool $newsletter = false;
}
