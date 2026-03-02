<?php

declare(strict_types=1);

namespace App\Application\Player\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class ForgotPasswordDTO
{
    #[Assert\NotBlank(message: 'validation.email_required')]
    #[Assert\Email(message: 'validation.email_invalid')]
    public ?string $email = null;
}
