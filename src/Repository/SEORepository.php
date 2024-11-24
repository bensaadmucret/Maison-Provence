<?php

namespace App\Repository;

use App\Entity\SEO;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SEO>
 *
 * @method SEO|null find($id, $lockMode = null, $lockVersion = null)
 * @method SEO|null findOneBy(array $criteria, array $orderBy = null)
 * @method SEO[]    findAll()
 * @method SEO[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SEORepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SEO::class);
    }

    /**
     * Trouve les configurations SEO qui n'ont pas d'URL canonique définie
     *
     * @return SEO[]
     */
    public function findWithoutCanonicalUrl(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.canonicalUrl IS NULL')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les configurations SEO avec des titres meta dépassant la longueur recommandée
     *
     * @return SEO[]
     */
    public function findWithLongMetaTitle(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('LENGTH(s.metaTitle) > 60')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les configurations SEO avec des descriptions meta dépassant la longueur recommandée
     *
     * @return SEO[]
     */
    public function findWithLongMetaDescription(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('LENGTH(s.metaDescription) > 160')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les configurations SEO qui ne sont pas indexables
     *
     * @return SEO[]
     */
    public function findNonIndexable(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.indexable = :indexable')
            ->setParameter('indexable', false)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les configurations SEO sans mots-clés
     *
     * @return SEO[]
     */
    public function findWithoutKeywords(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.metaKeywords IS NULL OR s.metaKeywords = :empty')
            ->setParameter('empty', '[]')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les configurations SEO sans données Open Graph
     *
     * @return SEO[]
     */
    public function findWithoutOpenGraphData(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.openGraphData IS NULL OR s.openGraphData = :empty')
            ->setParameter('empty', '[]')
            ->getQuery()
            ->getResult();
    }
}
