<?php


namespace App\Service;


use Symfony\Component\HttpFoundation\File\UploadedFile;

class Uploader
{
    /**
     * @var string
     */
    private $uploadPath;
    /**
     * @var string
     */
    private $shapePath;


    public function __construct(string $uploadPath, string $shapePath)
    {
        $this->uploadPath = $uploadPath;
        $this->shapePath = $shapePath;
    }

    public function uploadXML(UploadedFile $uploadedFile): string
    {
        $originalName = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);

        $destination = $this->uploadPath . '/normative_xml';
        $newFilename = $originalName . "-" . uniqid() . "." . $uploadedFile->guessClientExtension();

        $uploadedFile->move($destination, $newFilename);

        return $newFilename;

    }

    public function getSimpleXML(string $filePath): \SimpleXMLElement
    {
        $destination = $this->uploadPath . '/normative_xml/' . $filePath;
        return $xml = simplexml_load_file($destination);
    }


    /**
     * @return string
     */
    public function getShapePath(): string
    {
        return $this->shapePath;
    }

    /**
     * @param string $shapePath
     */
    public function setShapePath(string $shapePath): void
    {
        $this->shapePath = $shapePath;
    }



}