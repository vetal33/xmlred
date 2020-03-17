<?php


namespace App\Service;



class BaseXmlParser
{
    /**@var array All the points in xml-file */
    protected $points = [];

    /**@var array All the lines in xml-file */
    protected $polylines = [];

    protected $errors = [];

    /**
     * @param array $data
     * @return array|bool
     */
    protected function getPoints(array $data)
    {
        try {
            foreach ($data['InfoPart']['MetricInfo']['PointInfo']['Point'] as $value) {
                $this->points[$value['UIDP']]['X'] = $value['X'];
                $this->points[$value['UIDP']]['Y'] = $value['Y'];
            }
            return $this->points;
        } catch (\Exception $exception) {
            $this->errors[] = $exception->getMessage();
            return false;
        }
    }

    /**
     * @param array $data
     * @return array|bool
     */
    protected function getPolyline(array $data)
    {
        try {
            foreach ($data['InfoPart']['MetricInfo']['Polyline']['PL'] as $value) {
                $this->polylines[$value['ULID']] = $value['Points']['P'];
            }
            return $this->polylines;
        } catch (\Exception $exception) {
            $this->errors[] = $exception->getMessage();
            return false;
        }
    }

    /**
     * @param array $dataXml
     * @param array $keys
     * @return array
     */
    protected function findNode(array $dataXml, array $keys): array
    {
        foreach ($keys as $key) {
            if (is_array($dataXml) && array_key_exists($key, $dataXml)) {
                $dataXml = $dataXml[$key];
            } else {
                return [];
            }
        }

        return $dataXml;
    }

    /**
     * Перевіряє чи є Node кінцевим, чи скрадовим
     * @param array $data
     * @return bool
     */
    protected function ifArrayOrList(array $data): bool
    {
        if (!is_string(array_key_last($data))) {
            return true;
        }
        return false;
    }

    protected function getUlidInternal($internal)
    {
        $userdata = [];
        array_walk_recursive($internal, function ($item, $key) use (&$userdata) {
            if ($key === 'ULID') {
                $userdata[] = $item;
            }
        }, $userdata);

        return $userdata;
    }

    protected function array_intersect_key_withoutSort(array $data)
    {
        $dataCoordinate = array_map(function ($value) {
            if (array_key_exists((int)$value, $this->points)) {
                return $this->points[$value];
            }
        }, $data);

        return $dataCoordinate;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}