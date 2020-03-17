<?php


namespace App\Service;


use Symfony\Component\HttpFoundation\File\UploadedFile;

class XmlUploader extends Uploader
{
    const XML_NORMATIVE = 'xml_normative';

    private $errors = [];

    /**
     * Uploader constructor.
     * @param string $uploadPath
     */

    public function __construct(string $uploadPath)
    {
        parent::__construct($uploadPath);
    }

    /**
     * @param UploadedFile $uploadedFile
     */
    public function upload(UploadedFile $uploadedFile): void
    {
        $this->setOriginalName($uploadedFile);
        $destination = $this->uploadPath . '/' . self::XML_NORMATIVE;

        $uploadedFile->move($destination, $this->getNewName());
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
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}