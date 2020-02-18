<?php

namespace App\Repository;

use App\Entity\LocalFactorDir;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method LocalFactorDir|null find($id, $lockMode = null, $lockVersion = null)
 * @method LocalFactorDir|null findOneBy(array $criteria, array $orderBy = null)
 * @method LocalFactorDir[]    findAll()
 * @method LocalFactorDir[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LocalFactorDirRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LocalFactorDir::class);
    }

    // /**
    //  * @return LocalFactorDir[] Returns an array of LocalFactorDir objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?LocalFactorDir
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
