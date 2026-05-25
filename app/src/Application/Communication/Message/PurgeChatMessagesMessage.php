<?php

declare(strict_types=1);

namespace App\Application\Communication\Message;

final readonly class PurgeChatMessagesMessage
{
    public function __construct(
        public int $retentionHours = 24,
    ) {
    }
}
