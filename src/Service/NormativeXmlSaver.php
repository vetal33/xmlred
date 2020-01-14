<?php


namespace App\Service;

use App\Repository\FileRepository;
use Shapefile\Geometry\Linestring;
use Shapefile\Geometry\MultiPolygon;
use Shapefile\Geometry\Polygon;
use Shapefile\Shapefile;
use Shapefile\ShapefileException;
use Shapefile\ShapefileWriter;
use Shapefile\Geometry\Point;
use Shapefile\ShapefileReader;

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


    public function __construct(string $shapePath, FileRepository $fileRepository)
    {
        $this->shapePath = $shapePath;
        $this->fileRepository = $fileRepository;
    }

    public function toShape(array $coord)
    {
        try {
            $data = [];

            foreach ($coord as $key => $value) {
                $shapefileWriter = $this->createShapeFile($key);

                if (count(reset($value)) > 2) {
                    foreach ($value as $item => $valMulti) {
                        $linestring = $this->createLinestring($valMulti['coordinates']);
                        $polygon = $this->createPolygon($linestring);
                        $shapefileWriter->writeRecord($polygon);
                        $wkt = $this->convertToWGS($polygon);
                        $data[$key][$item]['coordinates'] = $this->getGeoJson($wkt);
                        $data[$key][$item]['name'] = $valMulti['ZoneNumber'];
                        $data[$key][$item]['km2'] = $valMulti['Km2'];
                    }
                } else {
                    $linestring = $this->createLinestring($value);
                    $polygon = $this->createPolygon($linestring);

                    $shapefileWriter->writeRecord($polygon);
                    $wkt = $this->convertToWGS($polygon);
                    $data[$key] = $this->getGeoJson($wkt);
                }
            }
            dump($data);
            return $data;

        } catch (ShapefileException $e) {
            // Print detailed error information
            echo "Error Type: " . $e->getErrorType()
                . "\nMessage: " . $e->getMessage()
                . "\nDetails: " . $e->getDetails();
        }
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
        $data = date('y-m-d');
        $destination = $this->shapePath . '/export/' . $name . '-' . $data . '-' . uniqid() . '.shp';
        /** @var ShapefileWriter $shapefileWriter */
        $shapefileWriter = new ShapefileWriter($destination);
        $shapefileWriter->setShapeType(Shapefile::SHAPE_TYPE_POLYGON);

        return $shapefileWriter;
    }

    private function convertToWGS(Polygon $polygon): string
    {
        $wkt = $this->fileRepository->transformPointFrom3857to4326($polygon->getWKT());

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

    private function addToZip(string $folder)
    {
        //TODO
    }


}