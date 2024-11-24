<?php

namespace App\Service;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;

class ProductService
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function getActiveProducts(): array
    {
        return $this->productRepository->findActiveProducts();
    }

    public function getProduct(int $id): Product
    {
        $product = $this->productRepository->find($id);
        
        if (!$product) {
            throw new EntityNotFoundException('Product not found');
        }

        return $product;
    }
}
