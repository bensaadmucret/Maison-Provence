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

    /**
     * Recherche de produits par terme.
     *
     * @return Product[]
     */
    public function searchProducts(string $searchTerm): array
    {
        $query = $this->createQueryBuilder('p')
            ->select('p', 'c', 'm')
            ->leftJoin('p.category', 'c')
            ->leftJoin('p.media', 'm')
            ->where('p.isActive = true')
            ->andWhere('LOWER(p.name) LIKE LOWER(:searchTerm) OR LOWER(p.description) LIKE LOWER(:searchTerm)')
            ->setParameter('searchTerm', '%'.$searchTerm.'%')
            ->orderBy('CASE 
                WHEN LOWER(p.name) LIKE LOWER(:exactTerm) THEN 1
                WHEN LOWER(p.name) LIKE LOWER(:startTerm) THEN 2
                ELSE 3
            END')
            ->setParameter('exactTerm', strtolower($searchTerm))
            ->setParameter('startTerm', strtolower($searchTerm).'%')
            ->getQuery();

        return $query
            ->enableResultCache(3600, sprintf('search_%s', md5($searchTerm)))
            ->getResult();
    }

    public function findActiveProductsPaginated(int $offset, int $limit, string $sortBy = 'name', string $order = 'asc'): array
    {
        $allowedSortFields = ['name', 'price', 'createdAt'];
        $sortBy = in_array($sortBy, $allowedSortFields) ? $sortBy : 'name';
        $order = 'desc' === strtolower($order) ? 'DESC' : 'ASC';

        $query = $this->createQueryBuilder('p')
            ->select('p', 'c', 'm')
            ->leftJoin('p.category', 'c')
            ->leftJoin('p.media', 'm')
            ->where('p.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('p.'.$sortBy, $order)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery();

        return $query
            ->enableResultCache(3600, sprintf('active_products_page_%d_sort_%s_%s', $offset / $limit + 1, $sortBy, $order))
            ->getResult();
    }

    public function findFeaturedProducts(int $limit = 4): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.isActive = :active')
            ->andWhere('p.isFeatured = :featured')
            ->setParameter('active', true)
            ->setParameter('featured', true)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findGalleryImages(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p', 'm')
            ->leftJoin('p.media', 'm')
            ->where('p.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
    }
}
