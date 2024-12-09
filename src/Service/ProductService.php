<?php

namespace App\Service;

use App\DTO\ProductDTO;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Service\CategoryService;
use App\Service\Interface\ProductServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Psr\Log\LoggerInterface;

class ProductService implements ProductServiceInterface
{
    private $logger;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProductRepository $productRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly SlugService $slugService,
        private readonly LoggingService $loggingService,
        private readonly CategoryService $categoryService,
        LoggerInterface $logger,
    ) {
        $this->logger = $logger;
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

        try {
            $product = $this->getProduct($id);

            // Détacher le produit de sa catégorie si elle existe
            if ($product->getCategory()) {
                $product->setCategory(null);
            }

            // Supprimer le produit
            $this->entityManager->remove($product);
            $this->entityManager->flush();

            $this->loggingService->logProductDeleted($id);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la suppression du produit : ' . $e->getMessage());
            throw $e;
        }
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

    public function createProductWithDefaultCategory(ProductDTO $productDTO): Product
    {
        // Ensure we have an Uncategorized category
        $defaultCategory = $this->categoryService->ensureUncategorizedCategory();

        $product = new Product();
        $product->setCategory($defaultCategory);
        
        // Set other product details from DTO
        $product->setName($productDTO->getName());
        $product->setDescription($productDTO->getDescription());
        $product->setPrice($productDTO->getPrice());
        $product->setStock($productDTO->getStock());
        
        // Set timestamps
        $now = new \DateTimeImmutable();
        $product->setCreatedAt($now);
        $product->setUpdatedAt($now);

        // Generate slug
        $slug = $this->slugService->generateSlug($product->getName());
        $product->setSlug($slug);

        // Set default active and featured status
        $product->setIsActive(true);
        $product->setIsFeatured(false);

        // Persist the product
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
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

        // Gestion explicite de la catégorie
        try {
            if (!$productDTO->getCategoryId()) {
                // Trouver une catégorie existante
                $defaultCategory = $this->categoryRepository->findOneBy([]);
                
                if (!$defaultCategory) {
                    // Créer une catégorie par défaut si aucune n'existe
                    $defaultCategory = $this->createDefaultCategory();
                }
                
                $product->setCategory($defaultCategory);
            } else {
                $category = $this->categoryRepository->find($productDTO->getCategoryId());
                if ($category) {
                    $product->setCategory($category);
                } else {
                    // Si la catégorie spécifiée n'existe pas, utiliser la catégorie par défaut
                    $defaultCategory = $this->categoryRepository->findOneBy([]) 
                        ?? $this->createDefaultCategory();
                    $product->setCategory($defaultCategory);
                }
            }
        } catch (\Exception $e) {
            // Log de l'erreur
            $this->logger->error('Erreur lors de l\'assignation de la catégorie : ' . $e->getMessage());
            
            // Assignation forcée d'une catégorie par défaut
            $defaultCategory = $this->createDefaultCategory();
            $product->setCategory($defaultCategory);
        }
    }

    private function createDefaultCategory(): Category
    {
        $defaultCategory = new Category();
        $defaultCategory->setName('Divers');
        $defaultCategory->setSlug('divers');
        $defaultCategory->setDescription('Catégorie par défaut');
        $defaultCategory->setCreatedAt(new \DateTimeImmutable());
        $defaultCategory->setUpdatedAt(new \DateTimeImmutable());
        
        $this->categoryRepository->save($defaultCategory, true);
        
        return $defaultCategory;
    }
}
