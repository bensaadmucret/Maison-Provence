<?php

namespace App\Tests\Service;

use App\DTO\ProductDTO;
use App\Entity\Category;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Service\ProductService;
use App\Service\SlugService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use PHPUnit\Framework\TestCase;

class ProductServiceTest extends TestCase
{
    private ProductRepository $productRepository;
    private CategoryRepository $categoryRepository;
    private EntityManagerInterface $entityManager;
    private SlugService $slugService;
    private ProductService $productService;

    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->slugService = $this->createMock(SlugService::class);

        $this->productService = new ProductService(
            $this->productRepository,
            $this->categoryRepository,
            $this->entityManager,
            $this->slugService
        );
    }

    public function testGetActiveProducts(): void
    {
        $product1 = new Product();
        $product1->setName('Product 1');
        $product1->setSlug('product-1');
        $product1->setIsActive(true);

        $product2 = new Product();
        $product2->setName('Product 2');
        $product2->setSlug('product-2');
        $product2->setIsActive(true);

        $this->productRepository
            ->expects($this->once())
            ->method('findActiveProducts')
            ->willReturn([$product1, $product2]);

        $result = $this->productService->getActiveProducts();

        $this->assertCount(2, $result);
        $this->assertSame($product1, $result[0]);
        $this->assertSame($product2, $result[1]);
    }

    public function testGetProductBySlug(): void
    {
        $product = new Product();
        $product->setName('Test Product');
        $product->setSlug('test-product');
        $product->setIsActive(true);

        $this->productRepository
            ->expects($this->once())
            ->method('findOneActiveBySlug')
            ->with('test-product')
            ->willReturn($product);

        $result = $this->productService->getProductBySlug('test-product');

        $this->assertSame($product, $result);
    }

    public function testCreateProduct(): void
    {
        $category = new Category();
        $category->setName('Test Category');

        $dto = new ProductDTO();
        $dto->setName('New Product');
        $dto->setDescription('Description');
        $dto->setPrice(19.99);
        $dto->setStock(10);
        $dto->setCategoryId(1);
        $dto->setIsActive(true);

        $this->categoryRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($category);

        $this->slugService
            ->expects($this->once())
            ->method('generateSlug')
            ->with('New Product')
            ->willReturn('new-product');

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Product::class));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $product = $this->productService->createProduct($dto);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals('New Product', $product->getName());
        $this->assertEquals('new-product', $product->getSlug());
        $this->assertEquals('Description', $product->getDescription());
        $this->assertEquals(19.99, $product->getPrice());
        $this->assertEquals(10, $product->getStock());
        $this->assertTrue($product->isActive());
        $this->assertSame($category, $product->getCategory());
    }

    public function testCreateProductWithInvalidCategory(): void
    {
        $dto = new ProductDTO();
        $dto->setName('New Product');
        $dto->setCategoryId(999); // ID inexistante

        $this->categoryRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Category not found');

        $this->productService->createProduct($dto);
    }

    public function testUpdateProduct(): void
    {
        $existingProduct = new Product();
        $existingProduct->setName('Old Name');
        $existingProduct->setSlug('old-name');

        $category = new Category();
        $category->setName('Test Category');

        $dto = new ProductDTO();
        $dto->setName('Updated Name');
        $dto->setDescription('Updated Description');
        $dto->setPrice(29.99);
        $dto->setStock(20);
        $dto->setCategoryId(1);
        $dto->setIsActive(true);

        $this->productRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($existingProduct);

        $this->categoryRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($category);

        $this->slugService
            ->expects($this->once())
            ->method('generateSlug')
            ->with('Updated Name')
            ->willReturn('updated-name');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $updatedProduct = $this->productService->updateProduct(1, $dto);

        $this->assertEquals('Updated Name', $updatedProduct->getName());
        $this->assertEquals('updated-name', $updatedProduct->getSlug());
        $this->assertEquals('Updated Description', $updatedProduct->getDescription());
        $this->assertEquals(29.99, $updatedProduct->getPrice());
        $this->assertEquals(20, $updatedProduct->getStock());
        $this->assertTrue($updatedProduct->isActive());
        $this->assertSame($category, $updatedProduct->getCategory());
    }

    public function testDeleteProduct(): void
    {
        $product = new Product();
        $product->setName('Product to Delete');

        $this->productRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($product);

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($product);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->productService->deleteProduct(1);
    }

    public function testDeleteNonExistentProduct(): void
    {
        $this->productRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Product not found');

        $this->productService->deleteProduct(999);
    }
}
