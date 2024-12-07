<?php

namespace App\Tests\Service;

use App\Entity\Category;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Service\ProductService;
use App\Service\SlugService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @extends TestCase
 */
class ProductServiceTest extends TestCase
{
    private ProductService $productService;
    private ProductRepository&MockObject $productRepository;
    private CategoryRepository&MockObject $categoryRepository;
    private SlugService&MockObject $slugService;
    private EntityManagerInterface&MockObject $entityManager;

    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->slugService = $this->createMock(SlugService::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->productService = new ProductService(
            $this->entityManager,
            $this->productRepository,
            $this->categoryRepository,
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
            ->expects(self::once())
            ->method('findBy')
            ->with(['isActive' => true])
            ->willReturn([$product1, $product2]);

        $result = $this->productService->getActiveProducts();

        self::assertCount(2, $result);
        self::assertSame($product1, $result[0]);
        self::assertSame($product2, $result[1]);
    }

    public function testGetProductBySlug(): void
    {
        $product = new Product();
        $product->setName('Test Product');
        $product->setSlug('test-product');
        $product->setIsActive(true);

        $this->productRepository
            ->expects(self::once())
            ->method('findOneActiveBySlug')
            ->with('test-product')
            ->willReturn($product);

        $result = $this->productService->getProductBySlug('test-product');

        self::assertSame($product, $result);
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
            ->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($category);

        $this->slugService
            ->expects(self::once())
            ->method('generateSlug')
            ->with('New Product')
            ->willReturn('new-product');

        $this->entityManager
            ->expects(self::once())
            ->method('persist')
            ->with(self::isInstanceOf(Product::class));

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        $product = $this->productService->createProduct($dto);

        self::assertInstanceOf(Product::class, $product);
        self::assertEquals('New Product', $product->getName());
        self::assertEquals('new-product', $product->getSlug());
        self::assertEquals('Description', $product->getDescription());
        self::assertEquals(19.99, $product->getPrice());
        self::assertEquals(10, $product->getStock());
        self::assertTrue($product->isActive());
        self::assertSame($category, $product->getCategory());
    }

    public function testCreateProductWithInvalidCategory(): void
    {
        $dto = new ProductDTO();
        $dto->setName('New Product');
        $dto->setCategoryId(999); // ID inexistante

        $this->categoryRepository
            ->expects(self::once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->slugService
            ->expects(self::once())
            ->method('generateSlug')
            ->with('New Product')
            ->willReturn('new-product');

        $product = $this->productService->createProduct($dto);
        self::assertNull($product->getCategory());
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
            ->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($existingProduct);

        $this->categoryRepository
            ->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($category);

        $this->slugService
            ->expects(self::once())
            ->method('generateSlug')
            ->with('Updated Name')
            ->willReturn('updated-name');

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        $updatedProduct = $this->productService->updateProduct(1, $dto);

        self::assertEquals('Updated Name', $updatedProduct->getName());
        self::assertEquals('updated-name', $updatedProduct->getSlug());
        self::assertEquals('Updated Description', $updatedProduct->getDescription());
        self::assertEquals(29.99, $updatedProduct->getPrice());
        self::assertEquals(20, $updatedProduct->getStock());
        self::assertTrue($updatedProduct->isActive());
        self::assertSame($category, $updatedProduct->getCategory());
    }

    public function testUpdateProductNotFound(): void
    {
        $dto = new ProductDTO();
        $dto->setName('Updated Name');

        $this->productRepository
            ->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn(null);

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Product not found');

        $this->productService->updateProduct(1, $dto);
    }

    public function testDeleteProduct(): void
    {
        $product = new Product();
        $product->setName('Product to Delete');

        $this->productRepository
            ->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($product);

        $this->entityManager
            ->expects(self::once())
            ->method('remove')
            ->with($product);

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        $result = $this->productService->deleteProduct(1);
        self::assertTrue($result);
    }

    public function testDeleteProductNotFound(): void
    {
        $this->productRepository
            ->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn(null);

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Product not found');

        $this->productService->deleteProduct(1);
    }

    public function testGetActiveProductsWithPagination(): void
    {
        $products = [];
        for ($i = 1; $i <= 15; ++$i) {
            $product = new Product();
            $product->setName("Product $i");
            $product->setSlug("product-$i");
            $product->setIsActive(true);
            $products[] = $product;
        }

        $page = 2;
        $limit = 6;
        $offset = ($page - 1) * $limit;

        $this->productRepository
            ->expects(self::once())
            ->method('findActiveProductsPaginated')
            ->with($offset, $limit, 'name', 'asc')
            ->willReturn(array_slice($products, $offset, $limit));

        $result = $this->productService->getActiveProducts($page, $limit, 'name', 'asc');

        self::assertCount($limit, $result);
        self::assertSame($products[$offset], $result[0]);
        self::assertSame($products[$offset + $limit - 1], $result[$limit - 1]);
    }

    public function testSearchProducts(): void
    {
        $product1 = new Product();
        $product1->setName('Savon de Marseille');
        $product1->setDescription('Savon traditionnel');
        $product1->setIsActive(true);

        $product2 = new Product();
        $product2->setName('Huile d\'olive');
        $product2->setDescription('Huile de Provence');
        $product2->setIsActive(true);

        $searchTerm = 'savon';

        $this->productRepository
            ->expects(self::once())
            ->method('searchProducts')
            ->with($searchTerm)
            ->willReturn([$product1]);

        $result = $this->productService->searchProducts($searchTerm);

        self::assertCount(1, $result);
        self::assertSame($product1, $result[0]);
    }

    public function testGetTotalActiveProducts(): void
    {
        $this->productRepository
            ->expects(self::once())
            ->method('count')
            ->with(['isActive' => true])
            ->willReturn(42);

        $result = $this->productService->getTotalActiveProducts();

        self::assertSame(42, $result);
    }

    public function testGetProductsWithLowStock(): void
    {
        $product1 = new Product();
        $product1->setName('Low Stock Product');
        $product1->setStock(2);
        $product1->setIsActive(true);

        $this->productRepository
            ->expects(self::once())
            ->method('findBy')
            ->with(
                ['isActive' => true],
                null,
                null,
                null,
                ['stock' => 'ASC']
            )
            ->willReturn([$product1]);

        $result = $this->productService->getProductsWithLowStock(5);

        self::assertCount(1, $result);
        self::assertSame($product1, $result[0]);
        self::assertLessThanOrEqual(5, $product1->getStock());
    }
}
