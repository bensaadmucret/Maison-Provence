<?php

namespace App\EventSubscriber;

use App\Entity\SiteConfiguration;
use App\Repository\SiteConfigurationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class MaintenanceSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SiteConfigurationRepository $configRepository,
        private Security $security,
        private Environment $twig,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 20],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Ne pas appliquer le mode maintenance pour les routes admin et login
        if (str_starts_with($request->getPathInfo(), '/admin')
            || str_starts_with($request->getPathInfo(), '/login')) {
            return;
        }

        // Ne pas appliquer le mode maintenance pour les utilisateurs admin
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $config = $this->configRepository->findOneBy([]) ?? new SiteConfiguration();

        if ($config->isMaintenanceMode()) {
            $content = $this->twig->render('maintenance.html.twig', [
                'message' => $config->getMaintenanceMessage(),
            ]);

            $event->setResponse(new Response($content, Response::HTTP_SERVICE_UNAVAILABLE));
        }
    }
}
