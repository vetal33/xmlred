<?php


namespace App\Service\Interfaces;


use Symfony\Component\HttpFoundation\File\UploadedFile;

abstract class Uploader
{
    /**
     * @var string
     */
    protected $uploadPath;

    /** @var string */
    protected $originalName;

    /**
     * @var string
     */
    private $uniquePostfix;

    abstract function upload(UploadedFile $uploadedFile): void;

    protected function __construct(string $uploadPath)
    {
        $this->uploadPath = $uploadPath;
        $this->uniquePostfix = uniqid();
    }

    /**
     * @param UploadedFile $uploadedFile
     */
    protected function setOriginalName(UploadedFile $uploadedFile)
    {
        if ($uploadedFile) {
            $this->originalName = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
            $this->originalName .= "." . $uploadedFile->guessClientExtension();
        }
    }

    /**
     * @return string|null
     */
    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    /**
     * @return string
     */
    public function getUniquePostfix(): string
    {
        return $this->uniquePostfix;
    }

    public function getNewName(): string
    {
        $arrayName = explode('.', $this->originalName);
        $ext = array_pop($arrayName);

        return implode('.', $arrayName) . "-" . $this->uniquePostfix . "." . $ext;
    }
}