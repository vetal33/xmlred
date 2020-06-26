<?php


namespace App\Service;


use App\Repository\FileRepository;
use DOMDocument;

class NormativeXmlValidator
{
    /**
     * @var string
     */
    private $xsdPath;

    private $errors = [];

    /** @var array */
    private $errorsGeom = [];
    /**
     * @var FileRepository
     */
    private $fileRepository;

    public function __construct(string $xsdPath, FileRepository $fileRepository)
    {
        $this->xsdPath = $xsdPath;
        $this->fileRepository = $fileRepository;
    }

    /**
     *
     * @param \SimpleXMLElement $fileXml
     *
     */
    public function validateStructure(\SimpleXMLElement $fileXml): void
    {
        $xml = new DOMDocument();
        $xml->loadXML($fileXml->asXML());

        /** Передає повноваження при обробці помилок користувачу       */
        $use_errors = libxml_use_internal_errors(true);

        $result = $xml->schemaValidate($this->xsdPath);
        if ($result === false) {
            $this->errors = libxml_get_errors();
        }
        libxml_use_internal_errors($use_errors);
    }

    public function validateGeom(array $parseJson)
    {
        $wktData = $this->prepareJsonToWkt($parseJson);
        $wktCheckGeomData = $this->validateGeomLayers($wktData);

    }

    private function prepareJsonToWkt(array $parseJson): array
    {
        $wktData = [];
        foreach ($parseJson as $layerName => $value) {
            if ($layerName === 'boundary') {
                $wktData[$layerName][0]['wkt'] = $this->fileRepository->getGeomFromJsonAsWkt($value['coordinates']);
            } else {
                foreach ($value as $indexLayer => $layerValue) {
                    $wktData[$layerName][$indexLayer]['wkt'] = $this->fileRepository->getGeomFromJsonAsWkt($layerValue['coordinates']);
                    $wktData[$layerName][$indexLayer]['name'] = array_key_exists('code', $layerValue) ? $layerValue['code'] : $layerValue['name'];
                }
            }
        }

        return $wktData;
    }


    private function validateGeomLayers($wktData)
    {
        foreach ($wktData as $layer => $values) {
            foreach ($values as $index => $value) {
                $isValid = $this->fileRepository->isValid($value['wkt']);
                if ($isValid === false) $this->errorsGeom[] = Array('layer' => $layer, 'name' => $value['name']);
                $wktData[$layer][$index]['result'] = $isValid;
            }
        }

        return $wktData;
    }


    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return array
     */
    public function getErrorsGeom(): array
    {
        return $this->errorsGeom;
    }
}