<?php


namespace App\Service;

use Shapefile\Geometry\Linestring;
use Shapefile\Geometry\MultiPolygon;
use Shapefile\Geometry\Polygon;
use Shapefile\Shapefile;
use Shapefile\ShapefileException;
use Shapefile\ShapefileWriter;
use Shapefile\Geometry\Point;
use Shapefile\ShapefileReader;

class NormativeXmlHandler
{

    /**
     * @var string
     */
    private $shapePath;

    public function __construct(string $shapePath)
    {
        $this->shapePath = $shapePath;
    }

    public function toShape(array $coord)
    {
        try {

            foreach ($coord as $key => $value) {
                $shapefileWriter = $this->createShapeFile($key);

                if (count(reset($value)) > 2) {
                    foreach ($value as $valMulti) {
                        $linestring = $this->createLinestring($valMulti);
                        $polygon = $this->createPolygon($linestring);
                        $shapefileWriter->writeRecord($polygon);
                    }
                } else {
                    $linestring = $this->createLinestring($value);
                    $polygon = $this->createPolygon($linestring);

                    $shapefileWriter ->writeRecord($polygon);
                }
            }

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
            $Point = new Point($coords['Y'], $coords['X']);
            $linestring->addPoint($Point);
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

}