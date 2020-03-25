<?php


namespace App\Service;


use App\Repository\FileRepository;
use App\Repository\LocalFactorDirRepository;
use Shapefile\Geometry\Linestring;
use Shapefile\Geometry\Point;
use Shapefile\Geometry\Polygon;

class BaseXmlSaver
{

    /**
     * @var FileRepository
     */
    protected $fileRepository;
    /**
     * @var LocalFactorDirRepository
     */
    protected $localFactorDirRepository;

    public function __construct(FileRepository $fileRepository, LocalFactorDirRepository $localFactorDirRepository)
    {
        $this->fileRepository = $fileRepository;
        $this->localFactorDirRepository = $localFactorDirRepository;
    }

    /**
     * Отримуємо номер зони системи координат СК-63, для подальшого вокорисатння у перерахунку
     *
     * @param array $array
     * @return bool|int
     */
    protected function getNumberZoneFromCoord(array $array)
    {
        //TODO треба переписать, не подобається

        if (array_key_exists('Y', $array['external'][0])) {
            $zone = substr($array['external'][0]['Y'], 0, 1);
            return (integer)('10630' . $zone);
        }
        return false;
    }

    /**
     * @param array $coord
     * @param array $coordInternal
     * @return Polygon
     */
    protected function arrayToPolygon(array $coord, array $coordInternal = []): Polygon
    {
        $linestringInternal = [];
        $linestringOuter = $this->createLinestring($coord);

        if ($coordInternal) {
            foreach ($coordInternal as $coord) {
                $linestringInternal[] = $this->createLinestring($coord);
            }
        }
        $polygon = $linestringInternal !== '' ? $this->createPolygon($linestringOuter, $linestringInternal) : $this->createPolygon($linestringOuter);

        return $polygon;
    }

    /**
     * @param array $coords
     * @return Linestring
     */
    private function createLinestring(array $coords): Linestring
    {
        $linestring = new Linestring();
        foreach ($coords as $key => $coord) {
            $point = new Point($coord['Y'], $coord['X']);
            $linestring->addPoint($point);
        }

        return $linestring;
    }

    /**
     * @param Linestring $linestringOut
     * @param array $linestringInArray
     * @return Polygon|bool
     */
    private function createPolygon(Linestring $linestringOut, array $linestringInArray = [])
    {
        if (!$linestringOut->isClosedRing()) {
            return false;
        }
        $polygon = new Polygon();
        $polygon->addRing($linestringOut);
        if ($linestringInArray) {
            foreach ($linestringInArray as $linestringIn) {
                if ($linestringIn->isClosedRing()) {
                    $polygon->addRing($linestringIn);
                }
            }
        }

        return $polygon;
    }

    /**
     * Конвертуєм координати із СК-63 в WGS
     *
     * @param Polygon $polygon
     * @param int $zone
     * @return string
     */
    protected function convertToWGS(Polygon $polygon, int $zone): string
    {
        //$wkt = $this->fileRepository->transformFeatureFromSC42toSC63($polygon->getWKT(), 28406);

        $wkt = $this->fileRepository->transformFeatureFromSC63to4326($polygon->getWKT(), $zone);
        //$wkt = $this->fileRepository->transformFeatureFromSC63to4326($simplifyGeom, $zone);

        /*                $wkt = $this->fileRepository->transformFeatureFromSC42toSC63($polygon->getWKT(), 28406);
                        $wkt = $this->fileRepository->transformFeatureFromSC63to4326($wkt, 106304);*/


        return $wkt;
    }

    /**
     * Перетворює WKT в GeoJson використовуючи бібліотеку для роботи з shp(Gaspare Sganga)
     *
     * @param string $wkt
     * @return array|string
     */
    protected function getGeoJson(string $wkt)
    {
        $wktPolygon = new Polygon();
        $wktPolygon->initFromWKT($wkt);

        return $wktPolygon->getGeoJSON();
    }

}