<?php

namespace App\Repository;

use App\Entity\PurposeDir;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PurposeDir|null find($id, $lockMode = null, $lockVersion = null)
 * @method PurposeDir|null findOneBy(array $criteria, array $orderBy = null)
 * @method PurposeDir[]    findAll()
 * @method PurposeDir[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PurposeDirRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PurposeDir::class);
    }

    // /**
    //  * @return PurposeDir[] Returns an array of PurposeDir objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?PurposeDir
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
