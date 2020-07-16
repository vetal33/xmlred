<?php


namespace App\Service;


use App\Entity\PurposeDir;
use App\Repository\PurposeDirRepository;

class FindPurpose
{
    /**
     * @var PurposeDirRepository
     */
    private $purposeDirRepository;

    public function __construct(PurposeDirRepository $purposeDirRepository)
    {
        $this->purposeDirRepository = $purposeDirRepository;
    }

    public function find(array $intersectArray): ?PurposeDir
    {
        if (array_key_exists('pub', $intersectArray) !== false) {
            return $this->findByCode($intersectArray['pub'][0]['purpose']);
        };

        return null;
    }

    private function findByCode(string $purpose): ?PurposeDir
    {
        $purposeDirs = $this->purposeDirRepository->findAll();
        foreach ($purposeDirs as $purposeDir) {
            if (mb_strpos($purpose, $purposeDir->getSubsection()) !== false) {

                return $purposeDir;
            }
        }

        return null;
    }
}