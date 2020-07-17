<?php

namespace App\Repository;

use App\Entity\PurposeDir;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use FOS\ElasticaBundle\Manager\RepositoryManagerInterface;

/**
 * @method PurposeDir|null find($id, $lockMode = null, $lockVersion = null)
 * @method PurposeDir|null findOneBy(array $criteria, array $orderBy = null)
 * @method PurposeDir[]    findAll()
 * @method PurposeDir[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PurposeDirRepository extends ServiceEntityRepository
{
    /**
     * @var RepositoryManagerInterface
     */
    private $finder;

    public function __construct(ManagerRegistry $registry, RepositoryManagerInterface $finder)
    {
        parent::__construct($registry, PurposeDir::class);
        $this->finder = $finder;
    }
}
