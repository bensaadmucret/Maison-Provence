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
        return $this->createQueryBuilder('p')
            ->where('p.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByCategory(Category $category): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.category = :category')
            ->andWhere('p.isActive = :active')
            ->setParameter('category', $category)
            ->setParameter('active', true)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneActiveBySlug(string $slug): ?Product
    {
        $qb = $this->createQueryBuilder('p');
        
        $result = $qb
            ->where('p.slug = :slug')
            ->andWhere('p.isActive = :active')
            ->setParameter('slug', $slug)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$result) {
            // Log if product exists but is inactive
            $inactiveProduct = $this->createQueryBuilder('p')
                ->select('p')
                ->where('p.slug = :slug')
                ->setParameter('slug', $slug)
                ->getQuery()
                ->getOneOrNullResult();

            if ($inactiveProduct instanceof Product) {
                error_log(sprintf(
                    'Product found but inactive - ID: %d, Name: %s, Slug: %s',
                    $inactiveProduct->getId(),
                    $inactiveProduct->getName(),
                    $inactiveProduct->getSlug()
                ));
            } else {
                error_log('No product found with slug: ' . $slug);
            }
        } else {
            error_log(sprintf(
                'Active product found - ID: %d, Name: %s, Slug: %s',
                $result->getId(),
                $result->getName(),
                $result->getSlug()
            ));
        }

        return $result;
    }
}
