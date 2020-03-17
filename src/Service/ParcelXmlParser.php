<?php


namespace App\Service;


use App\Repository\FileRepository;
use App\Service\Interfaces\ParcelParserInterface;
use App\Service\Interfaces\ParserXmlInterface;

class ParcelXmlParser extends BaseXmlParser implements ParserXmlInterface, ParcelParserInterface
{
    private $settingFields = [
        'parcelXml' => ['InfoPart', 'CadastralZoneInfo', 'CadastralQuarters', 'CadastralQuarterInfo', 'Parcels', 'ParcelInfo', 'ParcelMetricInfo'],
    ];

    /**
     * @var XmlFileUploader
     */
    private $xmlFileUploader;
    /**
     * @var ParcelXmlSaver
     */
    private $parcelXmlSaver;
    /**
     * @var FileRepository
     */
    private $fileRepository;

    public function __construct(XmlFileUploader $xmlFileUploader, ParcelXmlSaver $parcelXmlSaver, FileRepository $fileRepository)
    {
        $this->xmlFileUploader = $xmlFileUploader;
        $this->parcelXmlSaver = $parcelXmlSaver;
        $this->fileRepository = $fileRepository;
    }

    public function parse(\SimpleXMLElement $simpleXMLElement)
    {
        $array = json_decode(json_encode($simpleXMLElement), true);

        if ($this->getPolyline($array) && $this->getPoints($array)) {
            $result = $this->parseDataXml($array);

            return $result;
        }
        return null;
    }

    public function parseDataXml(array $dataXml)
    {
        $currentPoints = [];
        foreach ($this->settingFields as $value => $key) {
            $currentPoints[$value] = $this->findNode($dataXml, $key);
            $coords = $this->getGeometry($currentPoints[$value]);
            $currentPoints[$value]['external'] = $coords['external'];
        }

        return $currentPoints;
    }


    private function getGeometry(array $data)
    {
        $coordinates = [];

        if (array_key_exists('Externals', $data)) {
            $valueUlid = $this->getUlid($data['Externals']);

            if ($valueUlid) {
                $coordinates['external'] = $this->getCurrentPoints($valueUlid);
            }

            return $coordinates;
        } else {
            return array();
        }
    }


    /**
     * @param $externals
     * @return array
     */
    private function getUlid($externals): array
    {
        $userdata = [];
        array_walk_recursive($externals['Boundary'], function ($item, $key) use (&$userdata) {
            if ($key === 'ULID') {
                $userdata[] = $item;
            }
        }, $userdata);

        return $userdata;
    }

    private function getCurrentPoints(array $data)
    {
        $points = [];
        foreach ($data as $line) {
            if (array_key_exists((int)$line, $this->polylines)) {
                $points = array_merge($points, $this->polylines[$line]);
            }
        }

        if (!$points) return array();

        $points = array_unique($points);
        $dataCoordinate = $this->array_intersect_key_withoutSort($points);
        $dataCoordinate[] = reset($dataCoordinate);

        return $dataCoordinate;
    }

    public function getWktFromFileByName(string $fileName): ?string
    {
        $xmlObj = $this->xmlFileUploader->getSimpleXML($fileName);

        if (!$xmlObj) {
            $this->errors[] = 'Файл з ділянкою не знайдено!';

            return null;
        }
        $parseXml = $this->parse($xmlObj);

        if (!$parseXml) {
            $data['errors'] = $this->getErrors();

            return null;
        }
        $data = $this->parcelXmlSaver->toGeoJson($parseXml, false);
        $wkt = $this->fileRepository->getGeomFromJsonAsWkt($data['parcelXml']['coordinates']);

        return $wkt;
    }


}