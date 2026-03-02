<?php

declare(strict_types=1);

namespace App\Application\Player\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class ChangePasswordDTO
{
    #[Assert\NotBlank(message: 'validation.current_password_required')]
    public ?string $currentPassword = null;

    #[Assert\NotBlank(message: 'validation.new_password_required')]
    #[Assert\Length(
        min: 8,
        minMessage: 'validation.password_min_length'
    )]
    #[Assert\Regex(
        pattern: '/[A-Z]/',
        message: 'validation.password_uppercase'
    )]
    #[Assert\Regex(
        pattern: '/[a-z]/',
        message: 'validation.password_lowercase'
    )]
    #[Assert\Regex(
        pattern: '/[0-9]/',
        message: 'validation.password_digit'
    )]
    #[Assert\Regex(
        pattern: '/[!@#$%^&*()_+\-=\[\]{};\':\"\\|,.<>\/?~`]/',
        message: 'validation.password_special'
    )]
    #[Assert\PasswordStrength(
        minScore: Assert\PasswordStrength::STRENGTH_WEAK,
        message: 'validation.password_weak'
    )]
    #[Assert\NotCompromisedPassword(
        message: 'validation.password_compromised'
    )]
    public ?string $newPassword = null;

    #[Assert\NotBlank(message: 'validation.confirm_password_required')]
    #[Assert\EqualTo(
        propertyPath: 'newPassword',
        message: 'validation.password_mismatch'
    )]
    public ?string $confirmPassword = null;
}
