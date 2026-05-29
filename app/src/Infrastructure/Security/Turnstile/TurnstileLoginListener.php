<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\Turnstile;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Rejects login attempts when the Cloudflare Turnstile token is missing or
 * invalid. No-op when Turnstile is not configured.
 */
final readonly class TurnstileLoginListener implements EventSubscriberInterface
{
    public function __construct(
        private TurnstileVerifier $verifier,
        private RequestStack $requestStack,
        private TranslatorInterface $translator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckPassportEvent::class => ['onCheckPassport', 512],
        ];
    }

    public function onCheckPassport(CheckPassportEvent $event): void
    {
        if (!$this->verifier->isEnabled()) {
            return;
        }

        if (!$event->getAuthenticator() instanceof FormLoginAuthenticator) {
            return;
        }

        $request = $this->requestStack->getMainRequest();

        if ($request === null) {
            return;
        }

        $token = (string) $request->request->get('cf-turnstile-response', '');

        if (!$this->verifier->verify($token, $request->getClientIp())) {
            throw new CustomUserMessageAuthenticationException(
                $this->translator->trans('error.turnstile_invalid', [], 'player')
            );
        }
    }
}
