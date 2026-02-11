<?php

declare(strict_types=1);

namespace App\Application\Championship\Message;

final readonly class UpdateRankingMessage
{
    public function __construct(
        public \DateTimeImmutable $recordedAt,
    ) {
    }
}
