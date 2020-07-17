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

    public function findAll(): array
    {

        return $this->convertObjectToArray($this->purposeDirRepository->findAll());
    }

    private function convertObjectToArray(array $purposeDirs): array
    {
        $arr = [];
        $i = 0;
        foreach ($purposeDirs as $purposeDir) {
            $arr[$i]['id'] = $purposeDir->getId();
            $arr[$i]['subsection'] = $purposeDir->getSubsection();
            $arr[$i]['name'] = $purposeDir->getName();
            $arr[$i]['kfValue'] = $purposeDir->getKfValue();
            $i++;
        }

        return $arr;
    }

    public function convertToArray(array $purposeDirs): array
    {
        return $this->convertObjectToArray($purposeDirs);
    }

}