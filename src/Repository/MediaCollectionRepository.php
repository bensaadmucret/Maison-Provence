<?php

namespace App\Repository;

use App\Entity\MediaCollection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MediaCollection>
 *
 * @method MediaCollection|null find($id, $lockMode = null, $lockVersion = null)
 * @method MediaCollection|null findOneBy(array $criteria, array $orderBy = null)
 * @method MediaCollection[]    findAll()
 * @method MediaCollection[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MediaCollectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MediaCollection::class);
    }

    public function save(MediaCollection $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(MediaCollection $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('mc')
            ->where('mc.type = :type')
            ->setParameter('type', $type)
            ->orderBy('mc.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
