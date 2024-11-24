<?php

namespace App\Repository;

use App\Entity\SiteConfiguration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SiteConfiguration>
 *
 * @method SiteConfiguration|null find($id, $lockMode = null, $lockVersion = null)
 * @method SiteConfiguration|null findOneBy(array $criteria, array $orderBy = null)
 * @method SiteConfiguration[]    findAll()
 * @method SiteConfiguration[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SiteConfigurationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SiteConfiguration::class);
    }

    public function save(SiteConfiguration $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SiteConfiguration $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findCurrent(): ?SiteConfiguration
    {
        return $this->createQueryBuilder('sc')
            ->orderBy('sc.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
