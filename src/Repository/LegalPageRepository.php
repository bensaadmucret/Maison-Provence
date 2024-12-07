<?php

namespace App\Repository;

use App\Entity\LegalPage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LegalPage>
 *
 * @method LegalPage|null find($id, $lockMode = null, $lockVersion = null)
 * @method LegalPage|null findOneBy(array $criteria, array $orderBy = null)
 * @method LegalPage[]    findAll()
 * @method LegalPage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LegalPageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LegalPage::class);
    }

    public function findPublished(): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.isPublished = :val')
            ->setParameter('val', true)
            ->orderBy('l.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneBySlug(string $slug): ?LegalPage
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.slug = :slug')
            ->andWhere('l.isPublished = :published')
            ->setParameters([
                'slug' => $slug,
                'published' => true,
            ])
            ->getQuery()
            ->getOneOrNullResult();
    }
}
