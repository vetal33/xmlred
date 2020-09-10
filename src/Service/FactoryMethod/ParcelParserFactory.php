<?php


namespace App\Service\FactoryMethod;


use App\Service\Interfaces\ParcelParserInterface;
use App\Service\ParcelJsonParser;
use App\Service\ParcelXmlParser;

/**
 * Class ParcelParserFactory
 * @package App\Service\FactoryMethod
 */
class ParcelParserFactory
{

    /**
     * @var ParcelJsonParser
     */
    private $parcelJsonParser;
    /**
     * @var ParcelXmlParser
     */
    private $parcelXmlParser;

    public function __construct(ParcelJsonParser $parcelJsonParser, ParcelXmlParser $parcelXmlParser)
    {
        $this->parcelJsonParser = $parcelJsonParser;
        $this->parcelXmlParser = $parcelXmlParser;
    }

    /**
     * @param string $fileName
     * @return ParcelParserInterface|null
     */
    public function createParcelParser(string $fileName): ?ParcelParserInterface
    {
        $array = explode('.', $fileName);

        switch (end($array))
        {
            case 'xml':
                return $this->parcelXmlParser;
            case 'json':
                return  $this->parcelJsonParser;
            default:
                return null;
        }
    }
}