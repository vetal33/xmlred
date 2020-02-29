<?php

namespace App\Repository;

use App\Entity\Geom;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Geom|null find($id, $lockMode = null, $lockVersion = null)
 * @method Geom|null findOneBy(array $criteria, array $orderBy = null)
 * @method Geom[]    findAll()
 * @method Geom[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GeomRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Geom::class);
    }
}
