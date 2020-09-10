<?php


namespace App\Service;


use App\Entity\Parcel;
use App\Entity\User;
use App\Repository\IndexingRepository;
use App\Repository\LocalFactorDirRepository;
use App\Repository\ParcelRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ParcelHandler
{
    /**
     * @var ParcelRepository
     */
    private $parcelRepository;
    /**
     * @var JsonUploader
     */
    private $jsonUploader;
    /**
     * @var User|null
     */
    private $user;

    private $codeLocalTypeA = ['006', '008', '010', '012', '014'];

    private $codeLocalTypeB = ['007', '009', '011', '013', '015'];
    /**
     * @var LocalFactorDirRepository
     */
    private $localFactorDirRepository;

    private $errors = [];
    /**
     * @var FindPurpose
     */
    private $findPurpose;
    /**
     * @var IndexingRepository
     */
    private $indexingRepository;

    public function __construct(
        ParcelRepository $parcelRepository,
        JsonUploader $jsonUploader,
        TokenStorageInterface $tokenStorage,
        LocalFactorDirRepository $localFactorDirRepository,
        FindPurpose $findPurpose,
        IndexingRepository $indexingRepository)
    {
        $this->parcelRepository = $parcelRepository;
        $this->jsonUploader = $jsonUploader;
        $this->user = $tokenStorage->getToken()->getUser();
        $this->localFactorDirRepository = $localFactorDirRepository;
        $this->findPurpose = $findPurpose;
        $this->indexingRepository = $indexingRepository;
    }

    public function convertToJson(array $parcels): array
    {
        $parcelsToMap = [];
        $i = 0;
        /** @var  Parcel $parcel */
        foreach ($parcels as $parcel) {
            $parcelsToMap[$i]['area'] = number_format(round(($parcel->getArea()) / 10000, 4), 4) . ' га';
            $parcelsToMap[$i]['cadNum'] = $parcel->getCadNum();
            $parcelsToMap[$i]['wkt'] = $parcel->getGeom()->getOriginalGeom();
            $parcelsToMap[$i]['purpose'] = $parcel->getUse();


            $coordTransform = $this->parcelRepository->transformFeatureFromSC63to4326($parcel->getGeom()->getOriginalGeom());
            $parcelsToMap[$i]['extent'] = $this->parcelRepository->getExtent($coordTransform);
            $coordJson = $this->parcelRepository->getJsonFromWkt($coordTransform);
            $parcelsToMap[$i]['json'] = $coordJson;
            $i++;
        }

        return $parcelsToMap;
    }

    public function convertToJsonWithoutGeom(array $parcels): array
    {
        $parcelsToMap = [];
        $i = 0;
        /** @var  Parcel $parcel */
        foreach ($parcels as $parcel) {
            $parcelsToMap[$i]['area'] = number_format(round(($parcel->getArea()) / 10000, 4), 4) . ' га';
            $parcelsToMap[$i]['cadNum'] = $parcel->getCadNum();
            $coordTransform = $this->parcelRepository->transformFeatureFromSC63to4326($parcel->getGeom()->getOriginalGeom());
            $parcelsToMap[$i]['extent'] = $this->parcelRepository->getExtent($coordTransform);

            $i++;
        }

        return $parcelsToMap;
    }

    public function removeFile(string $fileName): void
    {
        if (file_exists($this->jsonUploader->getDestination() . '/' . $fileName)) {
            unlink($this->jsonUploader->getDestination() . '/' . $fileName);
        }
    }

    public function calculateNormative(array $intersectArray, int $year)
    {
        $calculateArray = [];
        try {
            $calculateArray['priceZone'] = round((float)$intersectArray['cmn'] * (float)$intersectArray['zone']['km2'], 2);
            $localTruth = $this->regulateLocals($intersectArray['local']);

            if (!$localTruth) {
                $calculateArray['priceLocal'] = 1;
                $calculateArray['local'] = '';
            } else {
                $result = $this->calculateLocals($localTruth, $intersectArray['local'], $intersectArray['area']);
                if (!$result) return null;
                $calculateArray['priceLocal'] = $this->calculateLocalsValue($result);
                $calculateArray['local'] = $result;
            }

            $calculateArray['area'] = $intersectArray['area'];

            $purposeDir = $this->findPurpose->find($intersectArray);

            $calculateArray['kf'] = (!is_null($purposeDir)) ? $purposeDir->getKfValue() : 1;
            $calculateArray['recommendPurpose'] = (!is_null($purposeDir)) ? $purposeDir->getSubsection() : '02.01';

            $calculateArray['purposeArr'] = $this->findPurpose->findAll();

            $calculateArray['priceByMeter'] = round((float)($calculateArray['priceZone']) * (float)$calculateArray['priceLocal'] * (float)$calculateArray['kf'], 2);
            $calculateArray['priceTotal'] = round($calculateArray['priceByMeter'] * round($calculateArray['area']), 2);

            $calculateArray['indexes'] = $this->getRateIndex($year);
            $calculateArray['indexes']['year'] = $year;

            if (mb_strpos($calculateArray['recommendPurpose'], '01.') !== false) {
                $calculateArray['priceTotalWithIndex'] = round(($calculateArray['priceTotal'] * $calculateArray['indexes']['sg']), 2);
                $calculateArray['indexes']['possible'] = $calculateArray['indexes']['sg'];
            } else {
                $calculateArray['priceTotalWithIndex'] = round(($calculateArray['priceTotal'] * $calculateArray['indexes']['noSg']), 2);
                $calculateArray['indexes']['possible'] = $calculateArray['indexes']['noSg'];
            }

            return $calculateArray;
        } catch (\Exception $exception) {
            $this->errors[] = $exception->getMessage();
            return false;
        }
    }

    /**
     * Проводить розрахунок локальних факторів
     *
     * @param array $locals
     * @return float
     */
    private function calculateLocalsValue(array $locals): float
    {
        $valueCalc = 1;
        foreach ($locals as $local) {
            $valueCalc *= (float)($local['index']);
        }

        return round($valueCalc, 2);
    }

    private function calculateLocals(array $localsTruth, array $locals, float $areaParcel): ?array
    {
        $result = [];
        foreach ($localsTruth as $localTruth) {
            foreach ($locals as $local) {
                if ($local['code'] === $localTruth) {
                    $localFactor = $this->localFactorDirRepository->findOneBy(['code' => $localTruth]);
                    if (!$localFactor) {
                        $this->errors[] = sprintf('Локальний фактор з кодом %s не було знайдено!', $localTruth);
                        return null;
                    }

                    $result[$localTruth]['index'] = $localFactor->getMaxValue();
                    $percent = round(($local['area'] / $areaParcel) * 100);
                    $result[$localTruth]['index'] = $localFactor->getMaxValue();
                    $result[$localTruth]['area'] = $percent;
                }
            }
        }

        return $result;
    }

    public function addMarkerToLocals(array $locals): array
    {
        $localsTruth = $this->regulateLocals($locals);
        foreach ($locals as $key => $local) {

            if (in_array($local['code'], $localsTruth)) {
                $locals[$key]['marker'] = 'check';
            } else {
                if (in_array($local['code'], $this->codeLocalTypeA)) {
                    $locals[$key]['marker'] = 'minus';
                }
                if (in_array($local['code'], $this->codeLocalTypeB)) {
                    $locals[$key]['marker'] = 'plus';
                }
            }
        }

        return $locals;
    }

    private function regulateLocals(array $locals)
    {
        $resultLocals = [];
        $localsCodes = $this->getLocalsCode($locals);
        foreach ($localsCodes as $local) {
            if (in_array($local, $this->codeLocalTypeA) || in_array($local, $this->codeLocalTypeB)) {
                if (in_array($local, $this->codeLocalTypeA)) {
                    $code = (int)($local) + 1;
                    $codeFromB = sprintf("%03d", $code);
                    if (!in_array($codeFromB, $localsCodes)) {
                        $resultLocals[] = $local;
                    }
                }
            } else {
                $resultLocals[] = $local;
            }
        }

        return array_unique($resultLocals);
    }

    private function getLocalsCode(array $locals)
    {
        return array_map(function ($value) {
            return $value['code'];
        }, $locals);
    }

    private function getRateIndex(string $currentYear)
    {
        $indexesArr = [];
        $indexesArr['noSg'] = 1;
        $indexesArr['sg'] = 1;

        if ($currentYear === '') return $indexesArr;

        $indexes = $this->indexingRepository->findAll();

        foreach ($indexes as $index) {
            if ($index->getYear() >= (int)$currentYear) {
                $indexesArr['noSg'] *= $index->getValueNonAgro();
                $indexesArr['sg'] *= $index->getValueAgro();
            }
        }
        $indexesArr['noSg'] = round($indexesArr['noSg'], 2);
        $indexesArr['sg'] = round($indexesArr['sg'], 2);

        return $indexesArr;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}