<?php

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Profiler\Profiler;

class ToolbarSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire(service: 'profiler')]
        private ?Profiler $profiler = null
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 256],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || !$this->profiler) {
            return;
        }

        $host = $event->getRequest()->getHost();
        $isLocal = $host === 'localhost' || str_starts_with($host, '127.');

        if (!$isLocal) {
            $this->profiler->disable();
        }
    }
}
