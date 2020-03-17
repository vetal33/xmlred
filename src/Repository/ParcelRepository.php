<?php

namespace App\Repository;

use App\Entity\Parcel;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Elastica\Query\BoolQuery;
use Elastica\Query\Nested;
use FOS\ElasticaBundle\Manager\RepositoryManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use function Doctrine\ORM\QueryBuilder;

/**
 * @method Parcel|null find($id, $lockMode = null, $lockVersion = null)
 * @method Parcel|null findOneBy(array $criteria, array $orderBy = null)
 * @method Parcel[]    findAll()
 * @method Parcel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ParcelRepository extends ServiceEntityRepository
{
    use GeometryTrait;
    /**
     * @var RepositoryManagerInterface
     */
    private $finder;
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;


    /**
     *
     * @param ManagerRegistry $registry
     * @param RepositoryManagerInterface $finder
     * @param TokenStorageInterface $tokenStorage
     */

    public function __construct(ManagerRegistry $registry, RepositoryManagerInterface $finder, TokenStorageInterface $tokenStorage)
    {
        parent::__construct($registry, Parcel::class);

        $this->finder = $finder;
        $this->tokenStorage = $tokenStorage;
    }

    public function search(string $findStr)
    {
        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        $boolQuery = new BoolQuery();

        $queryString = new \Elastica\Query\QueryString();
        $queryString->setQuery("*" . htmlentities(\Elastica\Util::escapeTerm($findStr), ENT_QUOTES) . "*");

        $userQuery = new Nested();
        $userQuery->setPath('userId');
        $userQuery->setQuery(new \Elastica\Query\Match('userId.id', $user->getId()));

        $boolQuery
            ->addMust($queryString)
            ->addFilter($userQuery);

        $parcels = $this->finder->getRepository(Parcel::class)->find($boolQuery);

        return $parcels;
    }
}
