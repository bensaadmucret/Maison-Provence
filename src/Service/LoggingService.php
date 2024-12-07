<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\Product;
use App\Service\Interface\LoggingServiceInterface;
use Psr\Log\LoggerInterface;

class LoggingService implements LoggingServiceInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    // Product logging methods
    public function logProductSearch(string $slug, array $context = []): void
    {
        $this->logger->info('Product search by slug', [
            'slug' => $slug,
            'action' => 'product_search',
            ...$context,
        ]);
    }

    public function logProductFound(Product $product, array $context = []): void
    {
        $this->logger->info('Product found', [
            'product_id' => $product->getId(),
            'name' => $product->getName(),
            'slug' => $product->getSlug(),
            'active' => $product->isActive(),
            'action' => 'product_found',
            ...$context,
        ]);
    }

    public function logProductNotFound(string $slug, array $context = []): void
    {
        $this->logger->warning('No active product found', [
            'slug' => $slug,
            'action' => 'product_not_found',
            ...$context,
        ]);
    }

    public function logProductUpdate(int $id, array $context = []): void
    {
        $this->logger->info('Product update initiated', [
            'product_id' => $id,
            'action' => 'product_update_start',
            ...$context,
        ]);
    }

    public function logProductUpdated(Product $product, array $context = []): void
    {
        $this->logger->info('Product updated', [
            'product_id' => $product->getId(),
            'name' => $product->getName(),
            'slug' => $product->getSlug(),
            'action' => 'product_updated',
            ...$context,
        ]);
    }

    public function logProductDeletion(int $id, array $context = []): void
    {
        $this->logger->info('Product deletion initiated', [
            'product_id' => $id,
            'action' => 'product_deletion_start',
            ...$context,
        ]);
    }

    public function logProductDeleted(array $context = []): void
    {
        $this->logger->info('Product deleted', [
            'action' => 'product_deleted',
            ...$context,
        ]);
    }

    public function logProductDetails(array $products, array $context = []): void
    {
        $productDetails = array_map(fn (Product $product) => [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'slug' => $product->getSlug(),
            'active' => $product->isActive(),
        ], $products);

        $this->logger->info('Product details', [
            'products' => $productDetails,
            'count' => count($products),
            'action' => 'product_details',
            ...$context,
        ]);
    }

    public function logDatabaseConnection(bool $success, ?\Exception $exception = null, array $context = []): void
    {
        if ($success) {
            $this->logger->info('Database connection successful', [
                'action' => 'database_connection_success',
                ...$context,
            ]);
        } else {
            $this->logger->error('Database connection failed', [
                'error' => $exception?->getMessage(),
                'action' => 'database_connection_failure',
                ...$context,
            ]);
        }
    }

    // Cart logging methods
    public function logCartOperation(string $operation, Cart $cart, array $context = []): void
    {
        $this->logger->info('Cart operation: '.$operation, [
            'operation' => $operation,
            'cart_id' => $cart->getId(),
            'user_id' => $cart->getUser()?->getId(),
            'total_items' => $cart->getItems()->count(),
            'total_price' => $cart->getTotalPrice(),
            'action' => 'cart_operation',
            ...$context,
        ]);
    }

    public function logCartError(?Cart $cart, ?\Throwable $exception = null, array $context = []): void
    {
        $this->logger->error('Cart operation error', [
            'cart_id' => $cart?->getId(),
            'exception' => $exception?->getMessage(),
            'action' => 'cart_error',
            ...$context,
        ]);
    }
}
