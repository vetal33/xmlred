<?php


namespace App\Service;

use App\Repository\FileRepository;
use App\Repository\LocalFactorDirRepository;
use App\Service\Interfaces\XmlSaverInterface;
use Shapefile\Geometry\Polygon;
use Shapefile\Shapefile;
use Shapefile\ShapefileException;
use Shapefile\ShapefileWriter;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class NormativeXmlSaver extends BaseXmlSaver implements XmlSaverInterface
{
    /**
     * @var string
     */
    private $shapePath;

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

    /** @var array */
    private $featureNormative = [];

    /**
     * NormativeXmlSaver constructor.
     * @param string $shapePath
     * @param XmlUploader $uploader
     * @param TokenStorageInterface $tokenStorage
     * @param FileRepository $fileRepository
     * @param LocalFactorDirRepository $localFactorDirRepository
     */

    public function __construct(string $shapePath,
                                XmlUploader $uploader,
                                TokenStorageInterface $tokenStorage,
                                FileRepository $fileRepository,
                                LocalFactorDirRepository $localFactorDirRepository)
    {
        parent::__construct($fileRepository, $localFactorDirRepository);
        $this->shapePath = $shapePath;
        $this->user = $tokenStorage->getToken()->getUser();
        $this->uniquePostfix = $uploader->getUniquePostfix();
    }

    /**
     * Створюєм GeoJson фай для подальшого відображення на карті
     *
     * @param array $coord
     * @param bool $ifConvert
     * @return array
     */
    public function toGeoJson(array $coord, bool $ifConvert = true): array
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

                    $wkt = ($ifConvert) ? $this->convertToWGS($polygon, $numberZone) : $polygon->getWKT();
                    $number = $this->fileRepository->numberOfPoints($wkt);

                    $data[$key][$item]['coordinates'] = $this->getGeoJson($wkt);
                    $data[$key][$item]['points'] = $number;

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
                    if (array_key_exists('RegionNumber', $valMulti)) {
                        $data[$key][$item]['name'] = $valMulti['RegionNumber'];
                        $data[$key][$item]['ki'] = $valMulti['RegionIndex'];
                    }
                }
            } else {
                $polygon = $this->arrayToPolygon($value['external']);
                $wkt = ($ifConvert) ? $this->convertToWGS($polygon, $numberZone) : $polygon->getWKT();
                $data[$key]['coordinates'] = $this->getGeoJson($wkt);
                $data[$key]['points'] = $this->fileRepository->numberOfPoints($wkt);

                foreach ($value as $k => $v) {
                    if ($k !== 'external') {
                        $data[$key][$k] = $v;
                    }
                }
            }
        }

        return $data;
    }

    private function getMinMaxValues(string $code): array
    {
        $minMaxArray = [];
        if (!empty($code)) {
            $localFactor = $this->localFactorDirRepository->findOneBy(['code' => $code]);
            if (!$localFactor) {
                return $minMaxArray;
            }
            $minMaxArray['min'] = $localFactor->getMinValue();
            $minMaxArray['max'] = $localFactor->getMaxValue();

            return $minMaxArray;
        }
        return $minMaxArray;
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
        if ($layerName === "zones") {
            $polygon->setData('name', $data['ZoneNumber']);
            $polygon->setData('km2', $data['Km2']);
        }
        if ($layerName === "regions") {
            $polygon->setData('name', $data['RegionNumber']);
            $polygon->setData('ki', $data['RegionIndex']);
        }

        return $polygon;
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
        if ($nameLayer === "zones") {
            $shapefileWriter->addCharField('name', 10);
            $shapefileWriter->addFloatField('km2', 4, 2);
        }
        if ($nameLayer === "regions") {
            $shapefileWriter->addCharField('name', 10);
            $shapefileWriter->addFloatField('ki', 4, 2);
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
        } else {
            $this->clearDir($pathName);
        }
    }

    private function clearDir(string $destinationFolder): void
    {
        $dir = array_diff(scandir($destinationFolder), ['.', '..']);
        foreach ($dir as $file) {
            unlink($destinationFolder . '/' . $file);
        }
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
     * @return bool|string
     */
    public function addToZip()
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

    public function intersect(array $layers, string $feature)
    {
        if (!array_key_exists('boundary', $layers)) {
            return false;
        }
        $geomBoundary = $this->fileRepository->getGeomFromJsonAsWkt($layers['boundary']['coordinates']);

        if (!$this->intersectBoundary($geomBoundary, $feature)) {
            $this->errors[] = 'Ділянка знаходиться за межами населеного пункту!';
            return false;
        }

        $this->intersectZones($layers['zones'], $feature);
        $this->intersectLocal($layers['localFactor'], $feature);

        return $this->featureNormative;
    }

    private function intersectBoundary(string $boundary, string $feature)
    {
        return $this->fileRepository->isIntersect($boundary, $feature) ?: false;
    }

    private function intersectZones(array $zones, string $feature)
    {
        $areaMax = 0;
        foreach ($zones as $zone) {
            $geomZone = $this->fileRepository->getGeomFromJsonAsWkt($zone['coordinates']);
            $geomIntersect = $this->fileRepository->isIntersectAsArea($geomZone, $feature);
            if ($geomIntersect) {
                $area = $this->fileRepository->calcArea($geomIntersect);
                if ($area > $areaMax) {
                    $areaMax = $area;
                    $this->featureNormative['zone']['name'] = $zone['name'];
                    $this->featureNormative['zone']['km2'] = $zone['km2'];
                }
            }
        }
    }

    private function intersectLocal(array $locals, string $feature)
    {
        $arrayCurrent = [];
        $id = 1;
        foreach ($locals as $local) {
            $geomLocal = $this->fileRepository->getGeomFromJsonAsWkt($local['coordinates']);
            if (!$this->fileRepository->isValid($geomLocal)) {
                $this->errors[] = sprintf('Локальний фактор "%s"  - не валідний!', $local['name'] );
                continue;
            }
            $geomIntersect = $this->fileRepository->isIntersectAsArea($geomLocal, $feature);

            if ($geomIntersect) {
                $area = $this->fileRepository->calcArea($geomIntersect);

                $geomIntersectTransform = $this->fileRepository->transformFeatureFromSC63to4326($geomIntersect);
                $jsonIntersectTransform = $this->fileRepository->getJsonFromWkt($geomIntersectTransform);

                $arrayCurrent['name'] = $local['name'];
                $arrayCurrent['area'] = $area;
                $arrayCurrent['code'] = $local['code'];
                $arrayCurrent['geom'] = $jsonIntersectTransform;
                $arrayCurrent['id'] = $id;
                $minMaxValues = $this->getMinMaxValues($local['code']);
                if ($minMaxValues) {
                    $arrayCurrent['minVal'] = $minMaxValues['min'];
                    $arrayCurrent['maxVal'] = $minMaxValues['max'];
                }
                $this->featureNormative['local'][] = $arrayCurrent;
                $id++;
            }
        }
        if (!array_key_exists('local', $this->featureNormative)) {
            $this->featureNormative['local'] = [];
        }
    }
}