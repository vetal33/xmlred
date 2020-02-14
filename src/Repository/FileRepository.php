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


    public function transformFeatureFromSC63to4326(string $feature, int $zone = 0)
    {
        $zone = ($zone === 0) ? $this->getZoneFromCoords($feature) : $zone;
        $stmt = $this->getEntityManager()
            ->getConnection()
            ->prepare('select ST_AsText(st_transform(st_transform(ST_GeomFromText(:polygon, :zone), 4284), 4326))');
        $stmt->bindParam(':polygon', $feature);
        $stmt->bindParam(':zone', $zone);
        $stmt->execute();

        return $stmt->fetchAll()['0']['st_astext'];
    }


    public function getJsonFromWkt(string $geom)
    {
        $srt = 'SELECT ST_AsGeoJSON(ST_GeomFromText(\'' . $geom . '\')) AS json';
        $stmt = $this->getEntityManager()
            ->getConnection()
            ->prepare($srt);
        $stmt->execute();
        $result = $stmt->fetchAll();

        return $result[0]['json'];
    }

    /**
     * Із файла у фигляді строки повертає WKT
     *
     * @param string $file
     * @return string
     * @throws \Doctrine\DBAL\DBALException
     */

    public function getGeomFromJsonAsWkt(string $file): string
    {
        $srt = 'SELECT ST_AsText(ST_GeomFromGeoJSON(\'' . $file . '\')) AS wkt';
        $stmt = $this->getEntityManager()
            ->getConnection()
            ->prepare($srt);
        $stmt->execute();
        $result = $stmt->fetchAll();

        return $result[0]['wkt'];
    }

    /**
     * Повертає зону в залежності від координати
     *
     * @param string $wkt
     * @return int
     */
    private function getZoneFromCoords(string $wkt): int
    {
        $array = explode('(', $wkt);
        $firstDigitFromCoord = substr($this->getCoordByTypeFeature($array[0], $wkt), 0, 1);

        return (integer)('10630' . $firstDigitFromCoord);
    }

    /**
     * Повертає першу координату із WKT формату
     *
     * @param string $typeFeature
     * @param string $wkt
     * @return string
     */
    private function getCoordByTypeFeature(string $typeFeature, string $wkt): string
    {
        $coord = [];
        if ($typeFeature === 'MULTIPOLYGON') {
            $result = explode('(((', $wkt);
            $coord = explode(' ', $result[1]);
        } elseif ($typeFeature === 'POLYGON'){
            $result = explode('((', $wkt);
            $coord = explode(' ', $result[1]);
        }

        return $coord[0];
    }

    /**
     * Перевіряє валідність полігону
     *
     * @param $geom
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function isValid($geom): bool
    {
        $srt = 'SELECT ST_IsValid(ST_GeomFromText(\'' . $geom . '\')) = true as is_valid';
        $stmt = $this->getEntityManager()
            ->getConnection()
            ->prepare($srt);

        $stmt->execute();
        $result = $stmt->fetchAll();

        return $result[0]['is_valid'];

    }

    /**
     * Перевіряє на перетин полігони
     *
     * @param $geom1
     * @param $geom2
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function isIntersect($geom1, $geom2): bool
    {
        $srt = 'SELECT ST_Intersects(ST_GeomFromText(\'' . $geom1 . '\'), ST_GeomFromText(\'' . $geom2 . '\') ) = true as is_valid';
        $stmt = $this->getEntityManager()
            ->getConnection()
            ->prepare($srt);

        $stmt->execute();
        $result = $stmt->fetchAll();

        return $result[0]['is_valid'];

    }

    /**
     * Перевіряє на перетин полігони повертає площу перетину
     *
     * @param $geom1
     * @param $geom2
     * @return mixed|null
     * @throws \Doctrine\DBAL\DBALException
     */
    public function isIntersectAsArea($geom1, $geom2)
    {
        $result = $this->isIntersect($geom1, $geom2);
        if (!$result) {
            return null;
        }
        $geomIntersect = $this->isIntersectAsGeom($geom1, $geom2);

        return $geomIntersect;
    }

    /**
     * Рахує площу
     *
     * @param $geom
     * @return mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    public function calcArea($geom)
    {
        $stmt = $this->getEntityManager()
            ->getConnection()
            ->prepare('select ST_Area(ST_GeomFromText(\'' . $geom . '\')) as area');
        $stmt->execute();
        $result = $stmt->fetchAll();

        return $result[0]['area'];
    }


    /**
     * Перевіряє на перетин полігони повертає площу перетину
     *
     * @param $geom1
     * @param $geom2
     * @return mixed
     * @throws \Doctrine\DBAL\DBALException
     */

    public function isIntersectAsGeom($geom1, $geom2)
    {
        $srt = 'SELECT ST_AsText(ST_Intersection(ST_GeomFromText(\'' . $geom1 . '\'), ST_GeomFromText(\'' . $geom2 . '\') )) as geom';
        $stmt = $this->getEntityManager()
            ->getConnection()
            ->prepare($srt);

        $stmt->execute();
        $result = $stmt->fetchAll();

        return $result[0]['geom'];
    }

    public function transformFromSK63To3857($wkt)
    {
        $zone = $this->getZoneFromCoords($wkt);

        $stmt = $this->getEntityManager()
            ->getConnection()
            ->prepare('select ST_AsText(st_transform(st_transform(ST_GeomFromText(:polygon, :zone), 4284), 3857))');
        $stmt->bindParam('polygon', $wkt);
        $stmt->bindParam(':zone', $zone);

        $stmt->execute();
        return $stmt->fetchAll()['0']['st_astext'];

    }

    /**
     * Повертає центроїд геометрії ($geom)
     *
     * @param $geom
     * @return bool|mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getCentroid($geom)
    {
        $stmt = $this->getEntityManager()
            ->getConnection()
            ->prepare('select ST_AsText(ST_Centroid(ST_GeomFromText(:POLYGON, 3857)))');
        $stmt->bindParam('POLYGON', $geom);

        $stmt->execute();
        $result = $stmt->fetchAll();

        if (empty($result)) {
            return false;
        }

        return $result['0']['st_astext'];
    }

    /**
     * @param string $wkt
     * @return array
     */
    public function wktPointToArray(string $wkt): array
    {
        $wkt = ltrim($wkt, 'POINT(');
        $wkt = rtrim($wkt, ')');
        $array = explode(' ', $wkt);

        return $array;
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
