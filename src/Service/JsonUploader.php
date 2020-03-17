<?php


namespace App\Service;


use App\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


class JsonUploader extends Uploader
{
    const JSON_FILE = 'json_file';

    /**
     * @var User|null
     */
    private $user;

    public function __construct(string $uploadPath, TokenStorageInterface $tokenStorage)
    {
        parent::__construct($uploadPath);
        $this->user = $tokenStorage->getToken()->getUser();
    }

    /**
     * @param UploadedFile $uploadedFile
     */
    public function upload(UploadedFile $uploadedFile): void
    {
        $this->setDestination(self::JSON_FILE, $this->user->getFolderName());
        $this->makeDir();
        $this->removeOldFileFromDir($this->destination);
        $this->setOriginalName($uploadedFile);
        $uploadedFile->move($this->destination, $this->getNewName());
    }

    public function loadFileAsStr(string $fileName): ?string
    {
        $this->setDestination(self::JSON_FILE, $this->user->getFolderName());
        $filePath = $this->destination . '/' . $fileName;

        if (file_exists($filePath)) {
            $str = file_get_contents($filePath);

            return $str;
        }

        return null;
    }
}