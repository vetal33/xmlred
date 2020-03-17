<?php


namespace App\Repository;


trait GeometryTrait
{
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
        } elseif ($typeFeature === 'POLYGON') {
            $result = explode('((', $wkt);
            $coord = explode(' ', $result[1]);
        }

        return $coord[0];
    }

    /**
     * Рахує площу
     *
     * @param $geom
     * @return mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getExtent($geom)
    {
        $stmt = $this->getEntityManager()
            ->getConnection()
            ->prepare('select ST_Extent(ST_GeomFromText(\'' . $geom . '\')) as extent');
        $stmt->execute();
        $result = $stmt->fetchAll();

        return $result[0]['extent'];
    }



}