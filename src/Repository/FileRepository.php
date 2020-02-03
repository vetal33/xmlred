<?php

namespace App\Repository;

use App\Entity\File;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method File|null find($id, $lockMode = null, $lockVersion = null)
 * @method File|null findOneBy(array $criteria, array $orderBy = null)
 * @method File[]    findAll()
 * @method File[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, File::class);
    }

    public function transformFeatureFromSC42toSC63(string $feature, int $zone)
    {
        $stmt = $this->getEntityManager()
            ->getConnection()
            ->prepare('select ST_AsText(st_transform(ST_SetSRID(ST_GeomFromText(:polygon, :zone), 28406), 106304))');
           // ->prepare('select ST_AsText(st_transform(ST_GeomFromText(:polygon, :zone), 106304))');
        $stmt->bindParam(':polygon', $feature);
        $stmt->bindParam(':zone', $zone);
        $stmt->execute();

        return $stmt->fetchAll()['0']['st_astext'];
    }


    public function transformFeatureFromSC63to4326(string $feature, int $zone)
    {
        $stmt = $this->getEntityManager()
            ->getConnection()
            ->prepare('select ST_AsText(st_transform(st_transform(ST_GeomFromText(:polygon, :zone), 4284), 4326))');
        $stmt->bindParam(':polygon', $feature);
        $stmt->bindParam(':zone', $zone);
        $stmt->execute();

        return $stmt->fetchAll()['0']['st_astext'];
    }

    // /**
    //  * @return File[] Returns an array of File objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?File
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
