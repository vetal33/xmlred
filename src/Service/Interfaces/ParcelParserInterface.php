<?php


namespace App\Service\Interfaces;


interface ParcelParserInterface
{
    public function getWktFromFileByName(string $fileName): ?string;
}