<?php

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;

class SecurityHeadersSubscriber implements EventSubscriberInterface
{
    private $params;
    private $kernel;

    public function __construct(ParameterBagInterface $params, KernelInterface $kernel)
    {
        $this->params = $params;
        $this->kernel = $kernel;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', 0],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Ne pas appliquer les en-têtes de sécurité pour le profiler en mode dev
        if ($this->kernel->isDebug()
            && (str_starts_with($request->getPathInfo(), '/_profiler')
             || str_starts_with($request->getPathInfo(), '/_wdt'))) {
            return;
        }

        $response = $event->getResponse();
        $headers = $this->params->get('security.headers');

        if (isset($headers['x_frame_options'])) {
            $response->headers->set('X-Frame-Options', $headers['x_frame_options']);
        }

        if (isset($headers['content_security_policy'])) {
            $csp = implode('; ', $headers['content_security_policy']);
            $response->headers->set('Content-Security-Policy', $csp);
        }
    }
}
