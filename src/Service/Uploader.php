<?php


namespace App\Service;


use Symfony\Component\HttpFoundation\File\UploadedFile;

class Uploader
{
    /**
     * @var string
     */
    private $uploadPath;

    public function __construct(string $uploadPath)
    {
        $this->uploadPath = $uploadPath;
    }

    public function uploadXML(UploadedFile $uploadedFile): string
    {
        $originalName = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);

        $destination = $this->uploadPath . '/normative_xml';
        $newFilename = $originalName . "-" . uniqid() . "." . $uploadedFile->guessClientExtension();

        $uploadedFile->move($destination, $newFilename);

        return $newFilename;

    }

    public function getXml(string $filePath): \SimpleXMLElement
    {
        $destination = $this->uploadPath . '/normative_xml/' . $filePath;
        return $xml = simplexml_load_file($destination);
    }

}