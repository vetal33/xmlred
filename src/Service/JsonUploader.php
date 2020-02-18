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
        $this->setOriginalName($uploadedFile);
        $destination = $this->uploadPath . '/' . self::JSON_FILE . '/' . $this->user->getFolderName();

        $uploadedFile->move($destination, $this->getNewName());
    }
}