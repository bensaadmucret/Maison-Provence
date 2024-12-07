<?php

namespace App\Service\Interface;

use App\DTO\ProductDTO;
use App\Entity\Product;

interface ProductServiceInterface
{
    /**
     * @return Product[]
     */
    public function getActiveProducts(): array;

    public function getProductBySlug(string $slug): ?Product;

    public function createProduct(ProductDTO $productDTO): Product;

    public function updateProduct(int $id, ProductDTO $productDTO): Product;

    public function deleteProduct(int $id): void;

    /**
     * @return Product[]
     */
    public function getSimilarProducts(Product $product, int $limit = 4): array;
}
