<?php

namespace App\Service\Cache;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProductCacheService
{
    private const CACHE_PREFIX = 'product_';
    private const CACHE_TTL = 3600; // 1 heure

    public function __construct(
        private readonly CacheService $cache,
        private readonly ProductRepository $productRepository,
        private readonly SluggerInterface $slugger,
    ) {
    }

    public function getProduct(string $slug): ?Product
    {
        $key = self::CACHE_PREFIX.$this->slugger->slug($slug);

        return $this->cache->get(
            $key,
            fn () => $this->productRepository->findOneBy(['slug' => $slug]),
            self::CACHE_TTL
        );
    }

    public function getActiveProducts(): array
    {
        return $this->cache->get(
            self::CACHE_PREFIX.'active',
            fn () => $this->productRepository->findBy(['isActive' => true]),
            self::CACHE_TTL
        );
    }

    public function invalidateProduct(Product $product): void
    {
        $this->cache->delete(self::CACHE_PREFIX.$product->getSlug());
        $this->cache->delete(self::CACHE_PREFIX.'active');
    }

    public function invalidateAll(): void
    {
        $this->cache->clear();
    }
}
