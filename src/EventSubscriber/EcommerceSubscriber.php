<?php

namespace App\EventSubscriber;

use App\Entity\SiteConfiguration;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EcommerceSubscriber implements EventSubscriberInterface
{
    private const PROTECTED_PATHS = [
        '/shop',
        '/register',
        '/profile',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // Ne pas bloquer les routes d'administration
        if (str_starts_with($path, '/admin')) {
            return;
        }

        // Vérifie si la route est protégée
        $isProtectedPath = false;
        foreach (self::PROTECTED_PATHS as $protectedPath) {
            if (str_starts_with($path, $protectedPath)) {
                $isProtectedPath = true;
                break;
            }
        }

        if (!$isProtectedPath) {
            return;
        }

        // Récupère la configuration du site
        $siteConfig = $this->entityManager->getRepository(SiteConfiguration::class)->findOneBy([]);

        if (!$siteConfig) {
            return;
        }

        // Si l'e-commerce est désactivé et que l'utilisateur n'est pas admin
        if (!$siteConfig->isEcommerceEnabled() && !$this->security->isGranted('ROLE_ADMIN')) {
            $this->security->logout();
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_home')));
        }
    }
}
