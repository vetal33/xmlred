<?php


namespace App\Service;


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

    /** @var string */
    protected $destination;

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

    protected function removeOldFileFromDir(string $destinationFolder): void
    {
        $dir = array_diff(scandir($destinationFolder), ['.', '..']);

        $curDateTime = new \DateTime();
        $curDateTime->setTimestamp(time());

        foreach ($dir as $file) {
            $fileUnixTime = filemtime($destinationFolder . '/' . $file);
            $fileDateTime = new \DateTime();
            $fileDateTime->setTimestamp($fileUnixTime);

            $difference = $curDateTime->diff($fileDateTime);

            if ((int)($difference->format('%h')) > 3 || (int)($difference->format('%d')) >= 1) {
                unlink($destinationFolder . '/' . $file);
            }
        }
    }

    protected function makeDir(): void
    {
        if (!file_exists($this->destination)) {
            mkdir($this->destination);
        }
    }

    protected function setDestination(string $nameFolder, string $userFolder)
    {
        $this->destination = $this->uploadPath . '/' . $nameFolder . '/' . $userFolder;
    }

    /**
     * @return string
     */
    public function getDestination(): string
    {
        return $this->destination;
    }
}