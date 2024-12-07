<?php

namespace App\Service\Interface;

use App\Entity\Cart;
use App\Entity\Product;

interface LoggingServiceInterface
{
    public function logProductSearch(string $slug, array $context = []): void;

    public function logProductFound(Product $product, array $context = []): void;

    public function logProductNotFound(string $slug, array $context = []): void;

    public function logProductUpdate(int $id, array $context = []): void;

    public function logProductUpdated(Product $product, array $context = []): void;

    public function logProductDetails(array $products, array $context = []): void;

    public function logCartOperation(string $operation, Cart $cart, array $context = []): void;
}
