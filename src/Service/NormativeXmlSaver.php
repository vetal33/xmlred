<?php


namespace App\Service;

use App\Repository\FileRepository;
use Shapefile\Geometry\Linestring;
use Shapefile\Geometry\Polygon;
use Shapefile\Shapefile;
use Shapefile\ShapefileException;
use Shapefile\ShapefileWriter;
use Shapefile\Geometry\Point;
use Shapefile\ShapefileReader;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class NormativeXmlSaver
{

    /**
     * @var string
     */
    private $shapePath;
    /**
     * @var FileRepository
     */
    private $fileRepository;

    private $errors = [];
    /**
     * @var Security
     */
    private $security;


    public function __construct(string $shapePath, FileRepository $fileRepository, Security $security)
    {
        $this->shapePath = $shapePath;
        $this->fileRepository = $fileRepository;
        $this->security = $security;
    }

    public function toGeoJson(array $coord)
    {
        $numberZone = $this->getNumberZoneFromCoord(reset($coord));

        $data = [];
        foreach ($coord as $key => $value) {
            if (count(reset($value)) > 2) {
                foreach ($value as $item => $valMulti) {
                    $polygon = $this->arrayToPolygon($valMulti['coordinates']);

                    $wkt = $this->convertToWGS($polygon, $numberZone);
                    $data[$key][$item]['coordinates'] = $this->getGeoJson($wkt);
                    $data[$key][$item]['name'] = $valMulti['ZoneNumber'];
                    $data[$key][$item]['km2'] = $valMulti['Km2'];
                }
            } else {
                $polygon = $this->arrayToPolygon($value);

                $wkt = $this->convertToWGS($polygon, $numberZone);
                $data[$key] = $this->getGeoJson($wkt);
            }
        }
        return $data;
    }

    private function getNumberZoneFromCoord(array $array)
    {
        if (array_key_exists('Y', $array[0])) {

            $zone = substr($array[0]['Y'],0,1);
            return (integer)('10630' . $zone);
        }
        return false;
    }

    public function toShape(array $coord)
    {
        try {
            foreach ($coord as $key => $value) {
                $shapeFileWriter = $this->createShapeFile($key);

                if (count(reset($value)) > 2) {
                    foreach ($value as $item => $valMulti) {
                        $polygon = $this->arrayToPolygon($valMulti['coordinates']);
                        $shapeFileWriter->writeRecord($polygon);
                    }
                } else {
                    $polygon = $this->arrayToPolygon($value);
                    $shapeFileWriter->writeRecord($polygon);
                }
            }

            return true;

        } catch (ShapefileException $e) {
            $this->errors[] = "Error Type: " . $e->getErrorType()
                . "\nMessage: " . $e->getMessage()
                . "\nDetails: " . $e->getDetails();
            // Print detailed error information
            /*            echo "Error Type: " . $e->getErrorType()
                            . "\nMessage: " . $e->getMessage()
                            . "\nDetails: " . $e->getDetails();*/

            dump($this->getErrors());
            return false;
        }
    }

    /**
     * @param array $coord
     * @return Polygon
     */
    private function arrayToPolygon(array $coord): Polygon
    {
        $linestring = $this->createLinestring($coord);
        $polygon = $this->createPolygon($linestring);

        return $polygon;
    }


    /**
     * @param Linestring $linestring
     * @return Polygon|null
     */
    private function createPolygon(Linestring $linestring): ?Polygon
    {
        $polygon = new Polygon();
        if ($linestring->isClosedRing()) {
            $polygon->addRing($linestring);
        }
        return $polygon;
    }

    /**
     * @param array $coord
     * @return Linestring
     */
    private function createLinestring(array $coord): Linestring
    {
        $linestring = new Linestring();
        foreach ($coord as $key => $coords) {
            $point = new Point($coords['Y'], $coords['X']);
            $linestring->addPoint($point);
        }
        return $linestring;
    }


    private function createShapeFile($name): ShapefileWriter
    {
        $userFolder = preg_replace('/[^\p{L}\p{N}\s]/u', '', $this->security->getUser()->getUsername());
        dump($userFolder);
        $this->makeDir($userFolder);
        $data = date('y-m-d');
        $destination = $this->shapePath . '/export/' . $userFolder . '/' . $name . '-' . $data . '-' . uniqid() . '.shp';
        dump($destination);


        /** @var ShapefileWriter $shapefileWriter */
        $shapefileWriter = new ShapefileWriter($destination);
        $shapefileWriter->setShapeType(Shapefile::SHAPE_TYPE_POLYGON);

        return $shapefileWriter;
    }

    private function makeDir(string $dir): void
    {
        $pathName = $this->shapePath . '/export/' . $dir;
        if (!file_exists($pathName)) {
            mkdir($pathName);
        }
    }

    private function convertToWGS(Polygon $polygon, int $zone): string
    {
        $wkt = $this->fileRepository->transformFeatureFromSC63to4326($polygon->getWKT(), $zone);

        return $wkt;
    }

    private function getGeoJson(string $wkt)
    {
        $wktPolygon = new Polygon();
        $wktPolygon->initFromWKT($wkt);

        return $wktPolygon->getGeoJSON();
    }

    private function getArray(string $wkt): array
    {
        $wktPolygon = new Polygon();
        $wktPolygon->initFromWKT($wkt);

        return $wktPolygon->getArray();
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }


    private function addToZip(string $folder)
    {
        //TODO
    }


}