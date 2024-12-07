<?php

namespace App\Tests\Service\Cache;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\Cache\CacheService;
use App\Service\Cache\ProductCacheService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\String\Slugger\AsciiSlugger;

class ProductCacheServiceTest extends TestCase
{
    private CacheService $cacheService;
    private ProductRepository $productRepository;
    private ProductCacheService $productCacheService;

    protected function setUp(): void
    {
        $this->cacheService = $this->createMock(CacheService::class);
        $this->productRepository = $this->createMock(ProductRepository::class);

        $this->productCacheService = new ProductCacheService(
            $this->cacheService,
            $this->productRepository,
            new AsciiSlugger()
        );
    }

    public function testGetProduct(): void
    {
        // Arrange
        $product = new Product();
        $product->setName('Test Product');
        $product->setSlug('test-product');

        $this->cacheService
            ->expects($this->once())
            ->method('get')
            ->with(
                'product_test-product',
                $this->callback(function ($callback) use ($product) {
                    $this->productRepository
                        ->expects($this->once())
                        ->method('findOneBy')
                        ->with(['slug' => 'test-product'])
                        ->willReturn($product);

                    return $callback() === $product;
                }),
                3600
            )
            ->willReturn($product);

        // Act
        $result = $this->productCacheService->getProduct('test-product');

        // Assert
        $this->assertSame($product, $result);
    }

    public function testGetActiveProducts(): void
    {
        // Arrange
        $products = [
            (new Product())->setName('Product 1'),
            (new Product())->setName('Product 2'),
        ];

        $this->cacheService
            ->expects($this->once())
            ->method('get')
            ->with(
                'product_active',
                $this->callback(function ($callback) use ($products) {
                    $this->productRepository
                        ->expects($this->once())
                        ->method('findBy')
                        ->with(['isActive' => true])
                        ->willReturn($products);

                    return $callback() === $products;
                }),
                3600
            )
            ->willReturn($products);

        // Act
        $result = $this->productCacheService->getActiveProducts();

        // Assert
        $this->assertSame($products, $result);
    }

    public function testInvalidateProduct(): void
    {
        // Arrange
        $product = new Product();
        $product->setSlug('test-product');

        $this->cacheService
            ->expects($this->exactly(2))
            ->method('delete')
            ->withConsecutive(
                ['product_test-product'],
                ['product_active']
            );

        // Act
        $this->productCacheService->invalidateProduct($product);
    }

    public function testInvalidateAll(): void
    {
        // Arrange
        $this->cacheService
            ->expects($this->once())
            ->method('clear');

        // Act
        $this->productCacheService->invalidateAll();
    }
}
