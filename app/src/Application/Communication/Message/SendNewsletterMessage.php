<?php

declare(strict_types=1);

namespace App\Application\Communication\Message;

/**
 * Dispatched when an admin clicks "send" on a newsletter — picked up by the
 * messenger worker so the (potentially long) per-recipient loop runs async
 * instead of blocking the admin HTTP request.
 */
final readonly class SendNewsletterMessage
{
    public function __construct(
        public int $newsletterId,
    ) {
    }
}
