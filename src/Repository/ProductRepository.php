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
        error_log('Début de findOneActiveBySlug avec slug : ' . $slug);
        
        // Vérifier d'abord si le slug existe dans la base de données
        $allProducts = $this->createQueryBuilder('p')
            ->select('p.id, p.slug, p.isActive, p.name')
            ->getQuery()
            ->getArrayResult();
            
        error_log('Tous les produits dans la base :');
        foreach ($allProducts as $product) {
            error_log(sprintf(
                'ID: %d, Nom: %s, Slug: %s, Actif: %s',
                $product['id'],
                $product['name'],
                $product['slug'],
                $product['isActive'] ? 'oui' : 'non'
            ));
        }
        
        $qb = $this->createQueryBuilder('p');
        
        $result = $qb
            ->where('p.slug = :slug')
            ->andWhere('p.isActive = :active')
            ->setParameter('slug', $slug)
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();

        if (empty($result)) {
            // Vérifions si le produit existe mais n'est pas actif
            $inactiveProduct = $this->createQueryBuilder('p')
                ->select('p.id, p.name, p.slug, p.isActive')
                ->where('p.slug = :slug')
                ->setParameter('slug', $slug)
                ->getQuery()
                ->getArrayResult();

            if (!empty($inactiveProduct)) {
                error_log('Produit trouvé mais inactif : ' . json_encode($inactiveProduct[0]));
            } else {
                error_log('Aucun produit trouvé avec le slug : ' . $slug);
            }
            
            // Log de la requête SQL
            $sql = $qb->getQuery()->getSQL();
            $params = $qb->getQuery()->getParameters();
            error_log('Requête SQL : ' . $sql);
            error_log('Paramètres : ' . json_encode($params->map(function($param) {
                return [$param->getName() => $param->getValue()];
            })->toArray()));
        } else {
            error_log('Produit trouvé : ' . json_encode([
                'id' => $result[0]->getId(),
                'name' => $result[0]->getName(),
                'slug' => $result[0]->getSlug(),
                'isActive' => $result[0]->isActive()
            ]));
        }

        return $result ? $result[0] : null;
    }
}
