<?php

namespace App\Service;

use App\DTO\ProductDTO;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Service\Interface\ProductServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;

class ProductService implements ProductServiceInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProductRepository $productRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly SlugService $slugService,
        private readonly LoggingService $loggingService,
    ) {
    }

    /**
     * @return Product[]
     */
    public function getActiveProducts(int $page = 1, int $limit = 12, string $sortBy = 'name', string $order = 'asc', bool $featured = false): array
    {
        $this->loggingService->logProductSearch('active products');
        $qb = $this->entityManager->createQueryBuilder()
            ->select('p')
            ->from(Product::class, 'p')
            ->where('p.isActive = :active')
            ->setParameter('active', true);

        if ($featured) {
            $qb->andWhere('p.isFeatured = :featured')
               ->setParameter('featured', true);
        }

        // Ajout du tri
        switch ($sortBy) {
            case 'price':
                $qb->orderBy('p.price', $order);
                break;
            case 'name':
            default:
                $qb->orderBy('p.name', $order);
        }

        $products = $qb->setFirstResult(($page - 1) * $limit)
                 ->setMaxResults($limit)
                 ->getQuery()
                 ->getResult();
        $this->loggingService->logProductDetails($products);

        return $products;
    }

    public function getTotalActiveProducts(bool $featured = false): int
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('COUNT(p.id)')
            ->from(Product::class, 'p')
            ->where('p.isActive = :active')
            ->setParameter('active', true);

        if ($featured) {
            $qb->andWhere('p.isFeatured = :featured')
               ->setParameter('featured', true);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @throws EntityNotFoundException
     */
    public function getProduct(int $id): Product
    {
        $product = $this->productRepository->find($id);

        if (!$product) {
            throw new EntityNotFoundException('Product not found');
        }

        return $product;
    }

    public function getProductBySlug(string $slug): ?Product
    {
        $this->loggingService->logProductSearch($slug);

        try {
            $conn = $this->entityManager->getConnection();
            $conn->connect();
            $this->loggingService->logDatabaseConnection(true);
        } catch (\Exception $e) {
            $this->loggingService->logDatabaseConnection(false, $e);
            throw $e;
        }

        $allProducts = $this->productRepository->findAll();
        $this->loggingService->logProductDetails($allProducts);

        $product = $this->productRepository->findOneActiveBySlug($slug);

        if ($product) {
            $this->loggingService->logProductFound($product);
        } else {
            $this->loggingService->logProductNotFound($slug);
        }

        return $product;
    }

    /**
     * Recherche de produits par terme.
     *
     * @return Product[]
     */
    public function searchProducts(string $searchTerm): array
    {
        $this->loggingService->logProductSearch($searchTerm);
        $products = $this->productRepository->searchProducts($searchTerm);
        $this->loggingService->logProductDetails($products);

        return $products;
    }

    /**
     * @return Product[]
     */
    public function getSimilarProducts(Product $product, int $limit = 4): array
    {
        if (!$product->getCategory()) {
            return [];
        }

        return $this->productRepository->findBy(
            ['category' => $product->getCategory(), 'isActive' => true],
            ['createdAt' => 'DESC'],
            $limit
        );
    }

    public function getPreviousProduct(Product $product): ?Product
    {
        return $this->productRepository->createQueryBuilder('p')
            ->where('p.id < :productId')
            ->andWhere('p.isActive = true')
            ->setParameter('productId', $product->getId())
            ->orderBy('p.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getNextProduct(Product $product): ?Product
    {
        return $this->productRepository->createQueryBuilder('p')
            ->where('p.id > :productId')
            ->andWhere('p.isActive = true')
            ->setParameter('productId', $product->getId())
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function createProduct(ProductDTO $productDTO): Product
    {
        $product = new Product();
        $this->updateProductFromDTO($product, $productDTO);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }

    /**
     * @throws EntityNotFoundException
     */
    public function updateProduct(int $id, ProductDTO $productDTO): Product
    {
        $this->loggingService->logProductUpdate($id);

        $product = $this->getProduct($id);
        $this->updateProductFromDTO($product, $productDTO);
        $this->entityManager->flush();

        $this->loggingService->logProductUpdated($product);

        return $product;
    }

    /**
     * @throws EntityNotFoundException
     */
    public function deleteProduct(int $id): void
    {
        $this->loggingService->logProductDeletion($id);

        $product = $this->getProduct($id);
        $this->entityManager->remove($product);
        $this->entityManager->flush();
    }

    public function getFeaturedProducts(int $limit = 4): array
    {
        return $this->productRepository->findFeaturedProducts($limit);
    }

    public function getGalleryImages(string $mediaUrl): array
    {
        $products = $this->productRepository->findGalleryImages();
        $galleryImages = [];

        foreach ($products as $product) {
            foreach ($product->getMedia() as $media) {
                $galleryImages[] = [
                    'url' => $mediaUrl.'/'.$media->getFilename(),
                    'alt' => $media->getAlt() ?? $product->getName(),
                ];
            }
        }

        return $galleryImages;
    }

    private function updateProductFromDTO(Product $product, ProductDTO $productDTO): void
    {
        if (null !== $productDTO->getName()) {
            $product->setName($productDTO->getName());
            // Generate slug only when name is updated
            if (!$productDTO->getSlug()) {
                $product->setSlug($this->slugService->generateSlug($productDTO->getName()));
            }
        }

        if (null !== $productDTO->getDescription()) {
            $product->setDescription($productDTO->getDescription());
        }

        if (null !== $productDTO->getPrice()) {
            $product->setPrice((float) $productDTO->getPrice());
        }

        if (null !== $productDTO->getStock()) {
            $product->setStock((int) $productDTO->getStock());
        }

        $product->setIsActive($productDTO->isActive() ?? true);

        if ($productDTO->getSlug()) {
            $product->setSlug($productDTO->getSlug());
        }

        if ($productDTO->getCategoryId()) {
            $category = $this->categoryRepository->find($productDTO->getCategoryId());
            if ($category) {
                $product->setCategory($category);
            }
        }
    }
}
