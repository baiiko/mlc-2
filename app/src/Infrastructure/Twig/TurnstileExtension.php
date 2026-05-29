<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class TurnstileExtension extends AbstractExtension
{
    public function __construct(
        private readonly string $siteKey,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('turnstile_site_key', fn (): string => $this->siteKey),
            new TwigFunction('turnstile_enabled', fn (): bool => $this->siteKey !== ''),
        ];
    }
}
