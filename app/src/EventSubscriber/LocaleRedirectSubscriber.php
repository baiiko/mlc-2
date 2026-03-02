<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleRedirectSubscriber implements EventSubscriberInterface
{
    private const SUPPORTED_LOCALES = ['fr', 'en'];

    private const DEFAULT_LOCALE = 'fr';

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 10],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof NotFoundHttpException) {
            return;
        }

        $request = $event->getRequest();
        $pathInfo = $request->getPathInfo();

        // Check if the path already starts with a supported locale
        foreach (self::SUPPORTED_LOCALES as $locale) {
            if (str_starts_with($pathInfo, '/' . $locale . '/') || $pathInfo === '/' . $locale) {
                return;
            }
        }

        // Redirect to the same path with default locale prefix
        $newPath = '/' . self::DEFAULT_LOCALE . $pathInfo;

        // Preserve query string
        $queryString = $request->getQueryString();

        if ($queryString) {
            $newPath .= '?' . $queryString;
        }

        $response = new RedirectResponse($newPath, 302);
        $event->setResponse($response);
    }
}
