<?php

namespace App\Service;

use App\DTO\ProductDTO;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;

class ProductService
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly SlugService $slugService,
    ) {
    }

    public function getActiveProducts(): array
    {
        error_log('=== Récupération des produits actifs ===');
        $products = $this->productRepository->findActiveProducts();
        error_log('Nombre de produits actifs trouvés : ' . count($products));
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
        error_log('Slug recherché : ' . $slug);
        
        // Vérifier la connexion à la base de données
        try {
            $conn = $this->entityManager->getConnection();
            $conn->connect();
            error_log('Connexion à la base de données OK');
        } catch (\Exception $e) {
            error_log('Erreur de connexion à la base de données : ' . $e->getMessage());
            throw $e;
        }
        
        // Récupérer tous les produits pour le débogage
        $allProducts = $this->productRepository->findAll();
        error_log('Nombre total de produits dans la base : ' . count($allProducts));
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
            error_log('Produit trouvé avec le slug ' . $slug);
            error_log(sprintf(
                'Détails du produit - ID: %d, Nom: %s, Slug: %s, Actif: %s',
                $product->getId(),
                $product->getName(),
                $product->getSlug(),
                $product->isActive() ? 'oui' : 'non'
            ));
        } else {
            error_log('Aucun produit actif trouvé avec le slug ' . $slug);
        }
        
        return $product;
    }

    public function getSimilarProducts(Product $product, int $limit = 4): array
    {
        return $this->productRepository->createQueryBuilder('p')
            ->where('p.category = :category')
            ->andWhere('p.id != :productId')
            ->andWhere('p.isActive = true')
            ->setParameter('category', $product->getCategory())
            ->setParameter('productId', $product->getId())
            ->setMaxResults($limit)
            ->orderBy('RANDOM()', 'ASC')
            ->getQuery()
            ->getResult();
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

    public function createProduct(ProductDTO $dto): Product
    {
        error_log('=== Création d\'un nouveau produit ===');
        error_log(sprintf(
            'Données reçues - Nom: %s, Prix: %.2f, Stock: %d, CategoryId: %d',
            $dto->getName(),
            $dto->getPrice(),
            $dto->getStock(),
            $dto->getCategoryId()
        ));

        $category = $this->categoryRepository->find($dto->getCategoryId());
        if (!$category) {
            throw new EntityNotFoundException('Category not found');
        }

        $product = new Product();
        $this->updateProductFromDTO($product, $dto, $category);
        
        $this->entityManager->persist($product);
        $this->entityManager->flush();
        
        error_log(sprintf(
            'Produit créé - ID: %d, Nom: %s, Slug: %s',
            $product->getId(),
            $product->getName(),
            $product->getSlug()
        ));

        return $product;
    }

    public function updateProduct(int $id, ProductDTO $dto): Product
    {
        error_log('=== Mise à jour du produit ' . $id . ' ===');
        
        $product = $this->getProduct($id);
        $category = $this->categoryRepository->find($dto->getCategoryId());
        if (!$category) {
            throw new EntityNotFoundException('Category not found');
        }

        $this->updateProductFromDTO($product, $dto, $category);
        
        $this->entityManager->flush();
        
        error_log(sprintf(
            'Produit mis à jour - ID: %d, Nom: %s, Slug: %s',
            $product->getId(),
            $product->getName(),
            $product->getSlug()
        ));

        return $product;
    }

    private function updateProductFromDTO(Product $product, ProductDTO $dto, $category): void
    {
        $product->setName($dto->getName());
        $product->setSlug($this->slugService->generateSlug($dto->getName()));
        $product->setDescription($dto->getDescription());
        $product->setPrice($dto->getPrice());
        $product->setStock($dto->getStock());
        $product->setIsActive($dto->isActive());
        $product->setCategory($category);
        $product->setUpdatedAt(new \DateTimeImmutable());
        
        if ($dto->getImage()) {
            $product->setImage($dto->getImage());
        }
    }

    public function deleteProduct(int $id): void
    {
        error_log('=== Suppression du produit ' . $id . ' ===');
        
        $product = $this->getProduct($id);
        $this->entityManager->remove($product);
        $this->entityManager->flush();
        
        error_log('Produit supprimé avec succès');
    }
}
