<?php


namespace App\Service;


use App\Service\Interfaces\ParserXmlInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NormativeXmlParser extends BaseXmlParser implements ParserXmlInterface
{

    /**
     * Setting tree-object that we used
     * @var array
     */

    private $settingFields = [
        'boundary' => ['InfoPart', 'TerritorialZoneInfo', 'Objects', 'Lands', 'LandsValuation', 'LandsValuationType', 'MunicipalUnitNormativValuation'],
        'zones' => ['InfoPart', 'TerritorialZoneInfo', 'Objects', 'Lands', 'LandsValuation', 'LandsValuationType', 'MunicipalUnitNormativValuation', 'EconPlanZones', 'EconPlanZone'],
        'localFactor' => ['InfoPart', 'TerritorialZoneInfo', 'Objects', 'Lands', 'LandsValuation', 'LandsValuationType', 'MunicipalUnitNormativValuation', 'LocalFactors', 'LocalFactor'],
        'lands' => ['InfoPart', 'TerritorialZoneInfo', 'Objects', 'Lands', 'LandsValuation', 'LandsValuationType', 'MunicipalUnitNormativValuation', 'AgroGroups', 'AgroGroup'],
        'regions' => ['InfoPart', 'TerritorialZoneInfo', 'Objects', 'Lands', 'LandsValuation', 'LandsValuationType', 'MunicipalUnitNormativValuation', 'EstimatedAreas', 'EstimatedArea'],
    ];


    /**
     * @param \SimpleXMLElement $simpleXMLElement
     * @return array|null
     */
    public function parse(\SimpleXMLElement $simpleXMLElement): ?array
    {
        $array = json_decode(json_encode($simpleXMLElement), true);
        if ($this->getPolyline($array) && $this->getPoints($array)) {
            $result = $this->parseDataXml($array);

            return $result;
        }

        return null;
    }

    public function getGeneralInformation($data)
    {
        $generalInfo = [];

        foreach ($data as $key => $value) {
            if ($key === 'AreaNP') {
                if (array_key_exists('Size', $value)) {
                    $generalInfo['Size'] = $value['Size'];
                }
                if (array_key_exists('MeasurementUnit', $value)) {
                    $generalInfo['MeasurementUnit'] = $value['MeasurementUnit'];
                }
            }
            if ($key === 'ValuationYear') {
                $generalInfo['ValuationYear'] = $value;
            }
            if ($key === 'DescriptionOfTerritory') {
                if (array_key_exists('Region', $value)) {
                    $generalInfo['Region'] = $value['Region'];
                }
                if (array_key_exists('District', $value)) {
                    $generalInfo['District'] = $value['District'];
                }
                if (array_key_exists('Rada', $value)) {
                    $generalInfo['Rada'] = $value['Rada'];
                }
                if (array_key_exists('MunicipalUnitName', $value)) {
                    $generalInfo['MunicipalUnitName'] = $value['MunicipalUnitName'];
                }
                if (array_key_exists('KOATUU', $value)) {
                    $generalInfo['KOATUU'] = $value['KOATUU'];
                }
                if (array_key_exists('Population', $value)) {
                    $generalInfo['Population'] = $value['Population'];
                }
            }
            if ($key === 'Km1') {
                if (array_key_exists('Km1Z', $value)) {
                    $generalInfo['Km1Z'] = $value['Km1Z'];
                }
            }
            if ($key === 'PriceM') {
                if (array_key_exists('Cnm', $value)) {
                    $generalInfo['Cnm'] = $value['Cnm'];
                }
            }
        }

        return $generalInfo;
    }

    public function parseDataXml(array $dataXml)
    {

        $currentPoints = [];
        foreach ($this->settingFields as $key => $value) {
            $currentPoints[$key] = $this->findNode($dataXml, $value);
            $currentPoints[$key] = $this->modifyArray($currentPoints[$key], $key);
            if ($this->ifArrayOrList($currentPoints[$key])) {
                foreach ($currentPoints[$key] as $item => $node) {
                    $currentPoints[$key][$item]['coordinates'] = $this->getGeometry($node);
                }
            } else {
                $coords = $this->getGeometry($currentPoints[$key]);
                $currentPoints[$key] = $this->getGeneralInformation($currentPoints[$key]);
                $currentPoints[$key]['external'] = $coords['external'];
            }
        }

        return $currentPoints;
    }

    /**
     * Змінюєм масив на вложений у випадку коли одна (зона, грунт, локальний фактор)
     *
     * @param array $data
     * @param string $value
     * @return array
     */
    private function modifyArray(array $data, string $value): array
    {
        $modifyArray = [];
        if ($value === "zones" && is_string(array_key_last($data))) {
            $modifyArray[0] = $data;
            return $modifyArray;
        }
        if ($value === "localFactor" && is_string(array_key_last($data))) {
            $modifyArray[0] = $data;
            return $modifyArray;
        }
        if ($value === "lands" && is_string(array_key_last($data))) {
            $modifyArray[0] = $data;
            return $modifyArray;
        }
        return $data;
    }

    private function getGeometry(array $data)
    {
        $coordinates = [];

        if (!array_key_exists('Externals', $data)) {
            return array();
        }

        if (!$data['Externals']) {
            $factor = array_key_exists('NameFactor', $data) ? $data['NameFactor'] : reset($data);

            throw new NotFoundHttpException('Контур "' . $factor . '" - не містить геометрії');
        }

        $valueUlid = $this->getUlid($data['Externals']);

        if ($valueUlid) {
            $coordinates['external'] = $this->getCurrentPoints($valueUlid);
        }
        if (array_key_exists('Internals', $data['Externals'])) {

            $internalCoords = $data['Externals']['Internals']['Boundary'];

            foreach ($internalCoords as $internalCoord) {
                if (is_array($internalCoord)) {
                    $valueUlidInternals = $this->getUlidInternal($internalCoord);
                    $coordinates['internal'][] = $this->getCurrentPoints($valueUlidInternals);
                }
            }
        }

        return $coordinates;
    }

    /**
     *
     * @param $externals
     * @return array
     */
    private function getUlid($externals): array
    {
        $userdata = [];

        array_walk_recursive($externals['Boundary'], function ($item, $key) use (&$userdata) {
            if ($key === 'ULID') {
                $userdata[]['ULID'] = $item;
            }
            if ($key === 'FP') {
                $userdata[count($userdata) - 1]['FP'] = $item;
            }
            if ($key === 'TP') {
                $userdata[count($userdata) - 1]['TP'] = $item;
            }
        }, $userdata);

        return $userdata;
    }

    private function getCurrentPoints(array $data)
    {
        $points = [];
        foreach ($data as $line) {
            if (array_key_exists((int)$line['ULID'], $this->polylines)) {
                if (array_key_exists('FP', $line)) {
                    $points = array_merge($points, array_reverse($this->polylines[$line['ULID']]));
                } else {
                    $points = array_merge($points, $this->polylines[$line['ULID']]);
                }
            }
        }

        if (!$points) return array();

        $points = array_unique($points);
        $dataCoordinate = $this->array_intersect_key_withoutSort($points);
        $dataCoordinate[] = reset($dataCoordinate);

        return $dataCoordinate;
    }
}