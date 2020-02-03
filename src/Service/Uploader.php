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

    /** @var string */
    private $originalName;

    /** @var string */
    private $newNameFile;

    const XML_NORMATIVE = 'normative_xml';
    /**
     * @var FilesystemInterface
     */
    private $filesystem;

    /**
     * @var string
     */
    private $uniquePostfix;


    private $errors = [];

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
        $this->uniquePostfix = uniqid();
    }


    /**
     * @param UploadedFile $uploadedFile
     */
    public function uploadFile(UploadedFile $uploadedFile): void
    {
        $originalName = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);

        $destination = $this->uploadPath . '/' . self::XML_NORMATIVE;

        $this->newNameFile = $originalName . "-" . $this->uniquePostfix . "." . $uploadedFile->guessClientExtension();
        $this->originalName = $originalName . "." . $uploadedFile->guessClientExtension();
        $uploadedFile->move($destination, $this->newNameFile);
    }

    /**
     * @param string $filePath
     * @return null|\SimpleXMLElement
     */
    public function getSimpleXML(string $filePath): ?\SimpleXMLElement
    {
        libxml_use_internal_errors(true);
        $destination = $this->uploadPath . '/' . self::XML_NORMATIVE . '/' . $filePath;
        $xml = simplexml_load_file($destination);
        if (!$xml) {
            foreach (libxml_get_errors() as $error) {
                $this->errors[] = $error->message;
            }
            return null;
        }
        return $xml;
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

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return string
     */
    public function getNewNameFile(): string
    {
        return $this->newNameFile;
    }

    /**
     * @return string
     */
    public function getUniquePostfix(): string
    {
        return $this->uniquePostfix;
    }

    /**
     * @return string
     */
    public function getUploadPath(): string
    {
        return $this->uploadPath;
    }
}