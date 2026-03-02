<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

use App\Infrastructure\Service\TmColorParser;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class TmColorExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('tm_color', [TmColorParser::class, 'toHtml'], ['is_safe' => ['html']]),
            new TwigFilter('tm_strip', [TmColorParser::class, 'stripColors']),
        ];
    }
}
