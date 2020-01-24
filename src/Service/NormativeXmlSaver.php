<?php


namespace App\Service;

use App\Repository\FileRepository;
use Shapefile\Geometry\Linestring;
use Shapefile\Geometry\Polygon;
use Shapefile\Shapefile;
use Shapefile\ShapefileException;
use Shapefile\ShapefileWriter;
use Shapefile\Geometry\Point;
use Symfony\Component\Security\Core\Security;

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

    /**
     * @var string
     */
    private $destination;

    /**
     * @var string
     */
    private $userFolderName;


    /**
     * NormativeXmlSaver constructor.
     * @param string $shapePath
     * @param FileRepository $fileRepository
     * @param Security $security
     */

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

            if (!array_key_exists('external', $value)) {
                foreach ($value as $item => $valMulti) {
                    if (array_key_exists('internal', $valMulti['coordinates'])) {
                        $polygon = $this->arrayToPolygon($valMulti['coordinates']['external'], $valMulti['coordinates']['internal']);
                    } else {
                        $polygon = $this->arrayToPolygon($valMulti['coordinates']['external']);
                    }

                    $wkt = $this->convertToWGS($polygon, $numberZone);
                    $data[$key][$item]['coordinates'] = $this->getGeoJson($wkt);

                    if (array_key_exists('ZoneNumber', $valMulti)) {
                        $data[$key][$item]['name'] = $valMulti['ZoneNumber'];
                        $data[$key][$item]['km2'] = $valMulti['Km2'];
                    }
                    if (array_key_exists('LocalFactorCode', $valMulti)) {
                        $data[$key][$item]['name'] = $valMulti['NameFactor'];
                        $data[$key][$item]['code'] = $valMulti['LocalFactorCode'];
                    }
                    if (array_key_exists('CodeAgroGroup', $valMulti)) {
                        $data[$key][$item]['code'] = $valMulti['CodeAgroGroup'];
                    }
                }
            } else {
                $polygon = $this->arrayToPolygon($value['external']);

                $wkt = $this->convertToWGS($polygon, $numberZone);
                $data[$key] = $this->getGeoJson($wkt);
            }
        }
        return $data;
    }

    private function getNumberZoneFromCoord(array $array)
    {
        if (array_key_exists('Y', $array['external'][0])) {
            $zone = substr($array['external'][0]['Y'], 0, 1);
            return (integer)('10630' . $zone);
        }
        return false;
    }

    public function toShape(array $coord)
    {
        try {
            $this->userFolderName = preg_replace('/[^\p{L}\p{N}\s]/u', '', $this->security->getUser()->getUsername());
            $this->makeDir($this->userFolderName);
            $this->destination = $this->shapePath . '/export/' . $this->userFolderName;

            foreach ($coord as $key => $value) {
                $shapeFileWriter = $this->createShapeFile($key);

                if (!array_key_exists('external', $value)) {
                    foreach ($value as $item => $valMulti) {
                        if (array_key_exists('internal', $valMulti['coordinates'])) {
                            $polygon = $this->arrayToPolygon($valMulti['coordinates']['external'], $valMulti['coordinates']['internal']);
                        } else {
                            $polygon = $this->arrayToPolygon($valMulti['coordinates']['external']);
                        }
                        $shapeFileWriter->writeRecord($polygon);
                    }
                } else {
                    $polygon = $this->arrayToPolygon($value['external']);
                    $shapeFileWriter->writeRecord($polygon);
                }
            }

            // $this->addToZip();
            return true;

        } catch (ShapefileException $e) {
            // Print detailed error information
            $this->errors[] = "Error Type: " . $e->getErrorType()
                . "\nMessage: " . $e->getMessage()
                . "\nDetails: " . $e->getDetails();
            return false;
        }
    }

    /**
     * @param array $coord
     * @param array $coordInternal
     * @return Polygon
     */
    private function arrayToPolygon(array $coord, array $coordInternal = []): Polygon
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
     * @param Linestring $linestringOut
     * @param array $linestringInArray
     * @return Polygon|null
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
        $fileName = $this->destination . '/' . $name . '_' . date('y-m-d') . '_' . uniqid() . '.shp';

        /** @var ShapefileWriter $shapefileWriter */
        $shapefileWriter = new ShapefileWriter($fileName);
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


    private function addToZip()
    {
        if ($this->destination) {
            chdir(sys_get_temp_dir());

            $zipFile = new \ZipArchive();
            $zipPath = $this->userFolderName . '_' . date('y-m-d') . '.zip';

            $result = $zipFile->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            if (!$result) {
                $this->errors = 'Не вдалось зберегти zip файл!';
                return false;
            }

            $dir = array_diff(scandir('C:/OSPanel/domains/xmlred/public/shp/export/vbitko3gmailcom'), ['.', '..']);

            foreach ($dir as $value) {
                $file = 'C:/OSPanel/domains/xmlred/public/shp/export/vbitko3gmailcom/' . $value;
                $zipFile->addFile($file, $value);
            }
            $zipFile->close();
            return sys_get_temp_dir() . $zipPath;
        } else {
            $this->errors = 'Виникли проблеми із збереження в  zip файл!';
            return false;
        }
    }
}