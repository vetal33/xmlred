<?php


namespace App\Service;

use App\Repository\FileRepository;
use Shapefile\Geometry\Linestring;
use Shapefile\Geometry\Polygon;
use Shapefile\Shapefile;
use Shapefile\ShapefileException;
use Shapefile\ShapefileWriter;
use Shapefile\Geometry\Point;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

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
     * @var object|string
     */
    private $user;

    /**
     * @var string
     */
    private $destination;

    /**
     * @var string
     */
    private $uniquePostfix;

    /**
     * NormativeXmlSaver constructor.
     * @param string $shapePath
     * @param FileRepository $fileRepository
     * @param Uploader $uploader
     * @param TokenStorageInterface $tokenStorage
     */

    public function __construct(string $shapePath,
                                FileRepository $fileRepository,
                                Uploader $uploader,
                                TokenStorageInterface $tokenStorage)
    {
        $this->shapePath = $shapePath;
        $this->fileRepository = $fileRepository;
        $this->user = $tokenStorage->getToken()->getUser();
        $this->uniquePostfix = $uploader->getUniquePostfix();
    }

    /**
     * Створюєм GeoJson фай для подальшого відображення на карті
     *
     * @param array $coord
     * @return array
     */
    public function toGeoJson(array $coord): array
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

    /**
     * Отримуємо номер зони системи координат СК-63, для подальшого вокорисатння у перерахунку
     *
     * @param array $array
     * @return bool|int
     */
    private function getNumberZoneFromCoord(array $array)
    {
        //TODO треба переписать, не подобається

        if (array_key_exists('Y', $array['external'][0])) {
            $zone = substr($array['external'][0]['Y'], 0, 1);
            return (integer)('10630' . $zone);
        }
        return false;
    }

    /**
     * Функція для створення shp файлів використовуєчи масив із вихідними даними
     *
     * @param array $coord
     * @return bool
     */
    public function toShape(array $coord): bool
    {
        try {
            $userFolderName = $this->user->getFolderName();
            $this->makeDir($userFolderName);
            $this->destination = $this->shapePath . '/export/' . $userFolderName;

            foreach ($coord as $key => $value) {
                $shapeFileWriter = $this->createShapeFile($key, $this->uniquePostfix);
                $shapeFileWriter = $this->createFields($shapeFileWriter, $key);

                if (!array_key_exists('external', $value)) {
                    foreach ($value as $item => $valMulti) {
                        if (array_key_exists('internal', $valMulti['coordinates'])) {
                            $polygon = $this->arrayToPolygon($valMulti['coordinates']['external'], $valMulti['coordinates']['internal']);
                        } else {
                            $polygon = $this->arrayToPolygon($valMulti['coordinates']['external']);
                        }

                        $polygon = $this->setDataToField($polygon, $key, $valMulti);
                        $shapeFileWriter->writeRecord($polygon);
                    }
                } else {
                    $polygon = $this->arrayToPolygon($value['external']);
                    $shapeFileWriter->writeRecord($polygon);
                }
            }

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
     * Додає дані в поля *.shp файлів
     *
     * @param Polygon $polygon
     * @param string $layerName
     * @param array $data
     * @return Polygon
     */
    private function setDataToField(Polygon $polygon, string $layerName, array $data): Polygon
    {
        if ($layerName === "localFactor") {
            $polygon->setData('name', $data['NameFactor']);
        }
        if ($layerName === "lands") {
            $polygon->setData('name', $data['CodeAgroGroup']);
        }
        if ($layerName === "zony") {
            $polygon->setData('name', $data['ZoneNumber']);
            $polygon->setData('km2', $data['Km2']);
        }

        return $polygon;
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
     * @param string $name
     * @param string $uniqueName
     * @return ShapefileWriter
     */
    private function createShapeFile(string $name, string $uniqueName): ShapefileWriter
    {
        $fileName = $this->destination . '/' . $name . '_' . date('y-m-d') . '_' . $uniqueName . '.shp';

        /** @var ShapefileWriter $shapefileWriter */
        $shapefileWriter = new ShapefileWriter($fileName);
        $shapefileWriter->setShapeType(Shapefile::SHAPE_TYPE_POLYGON);

        return $shapefileWriter;
    }

    /**
     * Додає поля в таблицю *.shp файлів
     *
     * @param ShapefileWriter $shapefileWriter
     * @param string $nameLayer
     * @return ShapefileWriter
     */
    private function createFields(ShapefileWriter $shapefileWriter, string $nameLayer): ShapefileWriter
    {
        $shapefileWriter->setCharset('utf-8');

        if ($nameLayer === "lands") {
            $shapefileWriter->addCharField('name', 10);
        }
        if ($nameLayer === "localFactor") {
            $shapefileWriter->addCharField('name');
        }
        if ($nameLayer === "zony") {
            $shapefileWriter->addCharField('name', 10);
            $shapefileWriter->addFloatField('km2', 4, 2);
        }

        return $shapefileWriter;
    }

    /**
     * @param string $dir
     */
    private function makeDir(string $dir): void
    {
        $pathName = $this->shapePath . '/export/' . $dir;
        if (!file_exists($pathName)) {
            mkdir($pathName);
        }
    }

    /**
     * Конвертуєм координати із СК-63 в WGS
     *
     * @param Polygon $polygon
     * @param int $zone
     * @return string
     */
    private function convertToWGS(Polygon $polygon, int $zone): string
    {
        $wkt = $this->fileRepository->transformFeatureFromSC63to4326($polygon->getWKT(), $zone);

        return $wkt;
    }

    /**
     * Перетворює WKT в GeoJson використовуючи бібліотеку для роботи з shp(Gaspare Sganga)
     *
     * @param string $wkt
     * @return array|string
     */
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

    /**
     * Створює файл zip в тимчасовій папці і додає туди всі файли з папки клієнта
     *
     * @param string $nameFile
     * @return bool|string
     */
    public function addToZip(string $nameFile)
    {
        chdir(sys_get_temp_dir()); //Змінюємо робочу папку, на Temp, тимчасово, для створення файлу

        $userFolderName = $this->user->getFolderName();
        $destinationFolder = $this->shapePath . '/export/' . $userFolderName;

        $zipFile = new \ZipArchive();
        $zipPath = $userFolderName . '_' . date('y-m-d') . '.zip';

        $result = $zipFile->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        if (!$result) {
            $this->errors = 'Не вдалось зберегти zip файл!';
            return false;
        }

        $dir = array_diff(scandir($destinationFolder), ['.', '..']);

        if (!$dir) {
            $this->errors = 'Не вдалось зберегти zip файл!, папка з файлами пуста!';
            return false;
        }
        foreach ($dir as $value) {
            $file = $destinationFolder . '/' . $value;
            $zipFile->addFile($file, $value);
        }
        $zipFile->close();

        return $zipPath;
    }
}