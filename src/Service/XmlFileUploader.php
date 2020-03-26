<?php


namespace App\Service;


use App\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class XmlFileUploader extends Uploader
{
    const XML_FILE = 'xml_file';

    /**
     * @var User|null
     */
    private $user;

    private $errors = [];

    public function __construct(string $uploadPath, TokenStorageInterface $tokenStorage)
    {
        parent::__construct($uploadPath);
        $this->user = $tokenStorage->getToken()->getUser();
    }


    function upload(UploadedFile $uploadedFile): void
    {
        $this->setDestination(self::XML_FILE, $this->user->getFolderName());
        $this->makeDir();
        $this->removeOldFileFromDir($this->destination);
        $this->setOriginalName($uploadedFile);
        $uploadedFile->move($this->destination, $this->getNewName());
    }

    /**
     * @param string $filePath
     * @return null|\SimpleXMLElement
     */
    public function getSimpleXML(string $filePath): ?\SimpleXMLElement
    {
        $this->setDestination(self::XML_FILE, $this->user->getFolderName());
        libxml_use_internal_errors(true);
        $destination = $this->destination . '/' . $filePath;

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
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}