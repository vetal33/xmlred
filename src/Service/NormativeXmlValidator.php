<?php


namespace App\Service;


use DOMDocument;

class NormativeXmlValidator
{
    /**
     * @var string
     */
    private $xsdPath;

    private $errors = [];

    public function __construct(string $xsdPath)
    {
        $this->xsdPath = $xsdPath;
    }

    /**
     *
     * @param \SimpleXMLElement $fileXml
     *
     */
    public function validate(\SimpleXMLElement $fileXml): void
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

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}