<?php

namespace App\EventListener;

use App\Entity\LegalPage;
use App\Entity\PageSEO;
use App\Entity\Product;
use App\Entity\ProductSEO;
use App\Service\SlugService;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SEOEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly SlugService $slugService,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $this->handleSEOEvent($args);
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $this->handleSEOEvent($args);
    }

    private function handleSEOEvent(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        try {
            match (true) {
                $entity instanceof LegalPage => $this->handleLegalPage($entity),
                $entity instanceof Product => $this->handleProduct($entity),
                default => null,
            };
        } catch (\Exception $e) {
            $this->logger->error('Error in SEO event handling', [
                'entity' => get_class($entity),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function handleLegalPage(LegalPage $page): void
    {
        // Génération du slug si nécessaire
        if (!$page->getSlug()) {
            $page->setSlug($this->slugService->generate($page->getTitle()));
        }

        // Mise à jour du SEO
        $seo = $page->getSeo();
        if ($seo instanceof PageSEO) {
            $seo->setIdentifier($page->getSlug());
            if (!$seo->getCanonicalUrl()) {
                $seo->setCanonicalUrl(
                    $this->urlGenerator->generate('legal_page_show',
                        ['slug' => $page->getSlug()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    )
                );
            }
        }
    }

    private function handleProduct(Product $product): void
    {
        // Génération du slug si nécessaire
        if (!$product->getSlug()) {
            $product->setSlug($this->slugService->generate($product->getName()));
        }

        // Mise à jour du SEO
        $seo = $product->getSeo();
        if ($seo instanceof ProductSEO) {
            if (!$seo->getCanonicalUrl()) {
                $seo->setCanonicalUrl(
                    $this->urlGenerator->generate('product_show',
                        ['slug' => $product->getSlug()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    )
                );
            }
        }
    }
}
