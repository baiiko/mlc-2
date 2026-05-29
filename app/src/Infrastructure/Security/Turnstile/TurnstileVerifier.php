<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\Turnstile;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Verifies a Cloudflare Turnstile token against the siteverify endpoint.
 * When the secret key is not configured, verification is bypassed (no-op),
 * so the captcha can be left disabled in dev.
 */
final readonly class TurnstileVerifier
{
    private const SITEVERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $secretKey,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->secretKey !== '';
    }

    public function verify(?string $token, ?string $clientIp = null): bool
    {
        if (!$this->isEnabled()) {
            return true;
        }

        if ($token === null || $token === '') {
            return false;
        }

        try {
            $response = $this->httpClient->request('POST', self::SITEVERIFY_URL, [
                'body' => array_filter([
                    'secret' => $this->secretKey,
                    'response' => $token,
                    'remoteip' => $clientIp,
                ], static fn (?string $v): bool => $v !== null && $v !== ''),
                'timeout' => 5,
            ]);

            $data = $response->toArray(false);
            $success = (bool) ($data['success'] ?? false);

            if (!$success) {
                $this->logger->warning('[Turnstile] verification failed', [
                    'errors' => $data['error-codes'] ?? [],
                    'clientIp' => $clientIp,
                ]);
            }

            return $success;
        } catch (\Throwable $e) {
            $this->logger->error('[Turnstile] siteverify call errored', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
