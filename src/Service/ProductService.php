<?php

namespace App\Service;

use App\DTO\ProductDTO;
use App\Entity\Category;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;

class ProductService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProductRepository $productRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly SlugService $slugService,
    ) {
    }

    /**
     * @return Product[]
     */
    public function getActiveProducts(): array
    {
        error_log('=== Récupération des produits actifs ===');
        $products = $this->productRepository->findBy(['isActive' => true]);
        error_log('Nombre de produits actifs trouvés : '.count($products));
        foreach ($products as $product) {
            error_log(sprintf(
                'Produit actif trouvé - ID: %d, Nom: %s, Slug: %s',
                $product->getId(),
                $product->getName(),
                $product->getSlug()
            ));
        }

        return $products;
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
        error_log('=== Recherche de produit par slug ===');
        error_log('Slug recherché : '.$slug);

        // Vérifier la connexion à la base de données
        try {
            $conn = $this->entityManager->getConnection();
            $conn->connect();
            error_log('Connexion à la base de données OK');
        } catch (\Exception $e) {
            error_log('Erreur de connexion à la base de données : '.$e->getMessage());
            throw $e;
        }

        // Récupérer tous les produits pour le débogage
        $allProducts = $this->productRepository->findAll();
        error_log('Nombre total de produits dans la base : '.count($allProducts));
        foreach ($allProducts as $p) {
            error_log(sprintf(
                'Produit en base - ID: %d, Nom: %s, Slug: %s, Actif: %s',
                $p->getId(),
                $p->getName(),
                $p->getSlug(),
                $p->isActive() ? 'oui' : 'non'
            ));
        }

        $product = $this->productRepository->findOneActiveBySlug($slug);

        if ($product) {
            error_log('Produit trouvé avec le slug '.$slug);
            error_log(sprintf(
                'Détails du produit - ID: %d, Nom: %s, Slug: %s, Actif: %s',
                $product->getId(),
                $product->getName(),
                $product->getSlug(),
                $product->isActive() ? 'oui' : 'non'
            ));
        } else {
            error_log('Aucun produit actif trouvé avec le slug '.$slug);
        }

        return $product;
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
    public function updateProduct(int $id, ProductDTO $dto): Product
    {
        error_log('=== Mise à jour du produit '.$id.' ===');

        $product = $this->getProduct($id);
        $this->updateProductFromDTO($product, $dto);
        $this->entityManager->flush();

        error_log(sprintf(
            'Produit mis à jour - ID: %d, Nom: %s, Slug: %s',
            $product->getId(),
            $product->getName(),
            $product->getSlug()
        ));

        return $product;
    }

    private function updateProductFromDTO(Product $product, ProductDTO $productDTO): void
    {
        if ($productDTO->getName() !== null) {
            $product->setName($productDTO->getName());
            // Generate slug only when name is updated
            if (!$productDTO->getSlug()) {
                $product->setSlug($this->slugService->generateSlug($productDTO->getName()));
            }
        }

        if ($productDTO->getDescription() !== null) {
            $product->setDescription($productDTO->getDescription());
        }

        if ($productDTO->getPrice() !== null) {
            $product->setPrice((float) $productDTO->getPrice());
        }

        if ($productDTO->getStock() !== null) {
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

    /**
     * @throws EntityNotFoundException
     */
    public function deleteProduct(int $id): bool
    {
        error_log('=== Suppression du produit '.$id.' ===');

        $product = $this->getProduct($id);
        $this->entityManager->remove($product);
        $this->entityManager->flush();

        error_log('Produit supprimé avec succès');

        return true;
    }
}
