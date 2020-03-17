<?php


namespace App\Service;


use App\Repository\ParcelRepository;
use App\Service\Interfaces\ParcelParserInterface;

class ParcelJsonParser implements ParcelParserInterface
{
    /**
     * @var JsonUploader
     */
    private $jsonUploader;

    private $errors = [];
    /**
     * @var ParcelRepository
     */
    private $parcelRepository;

    public function __construct(JsonUploader $jsonUploader, ParcelRepository $parcelRepository)
    {
        $this->jsonUploader = $jsonUploader;
        $this->parcelRepository = $parcelRepository;
    }

    public function getWktFromFileByName(string $fileName): ?string
    {
        $jsonStr = $this->jsonUploader->loadFileAsStr($fileName);
        if (!$jsonStr) {
            $this->errors[] = 'Файл з ділянкою не знайдено!';

            return null;
        }
        return $this->parcelRepository->getGeomFromJsonAsWkt($jsonStr);
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}