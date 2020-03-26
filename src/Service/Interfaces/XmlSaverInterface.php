<?php


namespace App\Service\Interfaces;


interface XmlSaverInterface
{
    public function toGeoJson(array $data, bool $isConvert): array;

}