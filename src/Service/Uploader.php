<?php


namespace App\Service;


use League\Flysystem\FilesystemInterface;
use Symfony\Component\HttpFoundation\File\File;
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
    /**
     * @var string
     */
    private $originalName;

    const XML_NORMATIVE = 'normative_xml';
    /**
     * @var FilesystemInterface
     */
    private $filesystem;

    /**
     * Uploader constructor.
     * @param string $uploadPath
     * @param string $shapePath
     * @param FilesystemInterface $publicUploadsFilesystem
     */

    public function __construct(string $uploadPath, string $shapePath, FilesystemInterface $publicUploadsFilesystem)
    {
        $this->uploadPath = $uploadPath;
        $this->shapePath = $shapePath;
        $this->filesystem = $publicUploadsFilesystem;
    }

    /**
     *
     * @param UploadedFile $uploadedFile
     * @return string
     */
    public function uploadXML(UploadedFile $uploadedFile): string
    {
        $originalName = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);

        $destination = $this->uploadPath . '/' . self::XML_NORMATIVE;

        $newFilename = $originalName . "-" . uniqid() . "." . $uploadedFile->guessClientExtension();
        $this->originalName = $originalName . "." . $uploadedFile->guessClientExtension();
        $uploadedFile->move($destination, $newFilename);

        return $newFilename;

    }

    public function download()
    {
        $filename = 'public/uploads/1.txt';

        $filesystem = $this->filesystem;
        $resource = $filesystem->readStream('1.txt');

        return $resource;
    }

    public function getSimpleXML(string $filePath): \SimpleXMLElement
    {
        $destination = $this->uploadPath . '/' . self::XML_NORMATIVE . '/' . $filePath;
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

    /**
     * @return string
     */
    public function getOriginalName(): string
    {
        return $this->originalName;
    }

}