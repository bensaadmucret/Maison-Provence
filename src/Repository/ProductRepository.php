<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 *
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findActiveProducts(): array
    {
        $query = $this->createQueryBuilder('p')
            ->select('p', 'c', 'm')
            ->leftJoin('p.category', 'c')
            ->leftJoin('p.media', 'm')
            ->where('p.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('p.name', 'ASC')
            ->getQuery();

        return $query
            ->enableResultCache(3600, 'active_products')
            ->getResult();
    }

    public function findByCategory(Category $category): array
    {
        $query = $this->createQueryBuilder('p')
            ->select('p', 'c', 'm')
            ->leftJoin('p.category', 'c')
            ->leftJoin('p.media', 'm')
            ->where('p.category = :category')
            ->andWhere('p.isActive = :active')
            ->setParameter('category', $category)
            ->setParameter('active', true)
            ->orderBy('p.name', 'ASC')
            ->getQuery();

        return $query
            ->enableResultCache(3600, sprintf('category_%d_products', $category->getId()))
            ->getResult();
    }

    public function findOneActiveBySlug(string $slug): ?Product
    {
        $query = $this->createQueryBuilder('p')
            ->select('p', 'c', 'm')
            ->leftJoin('p.category', 'c')
            ->leftJoin('p.media', 'm')
            ->where('p.slug = :slug')
            ->andWhere('p.isActive = :active')
            ->setParameter('slug', $slug)
            ->setParameter('active', true)
            ->getQuery();

        return $query
            ->enableResultCache(3600, sprintf('product_slug_%s', $slug))
            ->getOneOrNullResult();
    }
}
