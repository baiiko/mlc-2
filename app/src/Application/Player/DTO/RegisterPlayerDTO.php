<?php

declare(strict_types=1);

namespace App\Application\Player\DTO;

use App\Domain\Player\Entity\Player;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueEntity(fields: ['login'], entityClass: Player::class, message: 'validation.login_exists')]
#[UniqueEntity(fields: ['email'], entityClass: Player::class, message: 'validation.email_exists')]
final class RegisterPlayerDTO
{
    #[Assert\NotBlank(message: 'validation.login_required')]
    #[Assert\Length(
        min: 3,
        minMessage: 'validation.login_min_length'
    )]
    public ?string $login = null;

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

    #[Assert\IsTrue(message: 'validation.rules_required')]
    public bool $rulesAccepted = false;
}
