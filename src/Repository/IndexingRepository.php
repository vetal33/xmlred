<?php

namespace App\Repository;

use App\Entity\Indexing;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Indexing|null find($id, $lockMode = null, $lockVersion = null)
 * @method Indexing|null findOneBy(array $criteria, array $orderBy = null)
 * @method Indexing[]    findAll()
 * @method Indexing[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IndexingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Indexing::class);
    }

    // /**
    //  * @return Indexing[] Returns an array of Indexing objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Indexing
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
