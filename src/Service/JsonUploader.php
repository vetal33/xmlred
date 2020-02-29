<?php


namespace App\Service;


use App\Entity\User;
use App\Service\Interfaces\Uploader;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


class JsonUploader extends Uploader
{
    const JSON_FILE = 'json_file';

    /**
     * @var User|null
     */
    private $user;

    private $destination;

    public function __construct(string $uploadPath, TokenStorageInterface $tokenStorage)
    {
        parent::__construct($uploadPath);
        $this->user = $tokenStorage->getToken()->getUser();
    }

    public function setDestination()
    {
        $this->destination = $this->uploadPath . '/' . self::JSON_FILE . '/' . $this->user->getFolderName();
    }

    /**
     * @param UploadedFile $uploadedFile
     */
    public function upload(UploadedFile $uploadedFile): void
    {
        $this->setDestination();
        $this->makeDir();
        $this->removeOldFileFromDir($this->destination);
        $this->setOriginalName($uploadedFile);
        $uploadedFile->move($this->destination, $this->getNewName());
    }

    public function loadFileAsStr(string $fileName): ?string
    {

        $this->setDestination();
        $filePath = $this->destination . '/' . $fileName;

        if (file_exists($filePath)) {
            $str = file_get_contents($filePath);

            return $str;
        }
        return null;
    }

    /**
     * @return string
     */
    public function getDestination(): string
    {
        return $this->destination;
    }


    private function removeOldFileFromDir(string $destinationFolder): void
    {
        $dir = array_diff(scandir($destinationFolder), ['.', '..']);

        $curDateTime = new \DateTime();
        $curDateTime->setTimestamp(time());

        foreach ($dir as $file) {
            $fileUnixTime = filemtime($destinationFolder . '/' . $file);
            $fileDateTime = new \DateTime();
            $fileDateTime->setTimestamp($fileUnixTime);

            $difference = $curDateTime->diff($fileDateTime);

            if ((int)($difference->format('%h')) > 12 || (int)($difference->format('%d')) >= 1) {
                unlink($destinationFolder . '/' . $file);
            }
        }
    }

    /**
     *
     */
    private function makeDir(): void
    {
        if (!file_exists($this->destination)) {
            mkdir($this->destination);
        }
    }
}