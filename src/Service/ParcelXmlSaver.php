<?php


namespace App\Service;


use App\Service\Interfaces\XmlSaverInterface;

class ParcelXmlSaver extends BaseXmlSaver implements XmlSaverInterface
{

    public function toGeoJson(array $coord, bool $ifConvert = true): array
    {
        $numberZone = $this->getNumberZoneFromCoord(reset($coord));

        $data = [];
        foreach ($coord as $key => $value) {
            $polygon = $this->arrayToPolygon($value['external']);
            $wkt = ($ifConvert) ? $this->convertToWGS($polygon, $numberZone) : $polygon->getWKT();

            $data[$key]['coordinates'] = $this->getGeoJson($wkt);
            foreach ($value as $k => $v) {
                if ($k !== 'external') {
                    $data[$key][$k] = $v;
                }
            }

        }

        return $data;
    }
}