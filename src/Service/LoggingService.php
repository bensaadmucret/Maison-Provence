<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\Product;
use Psr\Log\LoggerInterface;

class LoggingService
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    // Product logging methods
    public function logProductSearch(string $slug): void
    {
        $this->logger->info('=== Recherche de produit par slug ===');
        $this->logger->info('Slug recherché : ' . $slug);
    }

    public function logProductFound(Product $product): void
    {
        $this->logger->info(sprintf(
            'Produit trouvé - ID: %d, Nom: %s, Slug: %s, Actif: %s',
            $product->getId(),
            $product->getName(),
            $product->getSlug(),
            $product->isActive() ? 'oui' : 'non'
        ));
    }

    public function logProductNotFound(string $slug): void
    {
        $this->logger->warning('Aucun produit actif trouvé avec le slug: ' . $slug);
    }

    public function logProductUpdate(int $id): void
    {
        $this->logger->info('=== Mise à jour du produit ' . $id . ' ===');
    }

    public function logProductUpdated(Product $product): void
    {
        $this->logger->info(sprintf(
            'Produit mis à jour - ID: %d, Nom: %s, Slug: %s',
            $product->getId(),
            $product->getName(),
            $product->getSlug()
        ));
    }

    public function logProductDeletion(int $id): void
    {
        $this->logger->info('=== Suppression du produit ' . $id . ' ===');
    }

    public function logProductDeleted(): void
    {
        $this->logger->info('Produit supprimé avec succès');
    }

    public function logDatabaseConnection(bool $success, ?\Exception $exception = null): void
    {
        if ($success) {
            $this->logger->info('Connexion à la base de données OK');
        } else {
            $this->logger->error('Erreur de connexion à la base de données : ' . $exception?->getMessage());
        }
    }

    public function logProductDetails(array $products): void
    {
        $this->logger->info('Nombre total de produits : ' . count($products));
        foreach ($products as $product) {
            $this->logger->info(sprintf(
                'Produit - ID: %d, Nom: %s, Slug: %s, Actif: %s',
                $product->getId(),
                $product->getName(),
                $product->getSlug(),
                $product->isActive() ? 'oui' : 'non'
            ));
        }
    }

    // Cart logging methods
    public function logCartUpdate(Cart $cart): void
    {
        $this->logger->info(sprintf(
            'Panier mis à jour - ID: %d, Nombre d\'articles: %d, Total: %.2f€',
            $cart->getId(),
            $cart->getItems()->count(),
            $cart->getTotal()
        ));
    }

    public function logCartCleared(Cart $cart): void
    {
        $this->logger->info(sprintf(
            'Panier vidé - ID: %d',
            $cart->getId()
        ));
    }

    public function logCartError(string $message, ?\Exception $exception = null): void
    {
        $this->logger->error('Erreur panier : ' . $message);
        if ($exception) {
            $this->logger->error('Exception : ' . $exception->getMessage());
            $this->logger->debug('Stack trace : ' . $exception->getTraceAsString());
        }
    }
}
