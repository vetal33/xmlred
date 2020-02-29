<?php


namespace App\Service;


use App\Entity\Parcel;
use App\Entity\User;
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

    public function __construct(
        ParcelRepository $parcelRepository,
        JsonUploader $jsonUploader,
        TokenStorageInterface $tokenStorage,
        LocalFactorDirRepository $localFactorDirRepository)
    {
        $this->parcelRepository = $parcelRepository;
        $this->jsonUploader = $jsonUploader;
        $this->user = $tokenStorage->getToken()->getUser();
        $this->localFactorDirRepository = $localFactorDirRepository;
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
            $coordJson = $this->parcelRepository->getJsonFromWkt($coordTransform);
            $parcelsToMap[$i]['json'] = $coordJson;
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

    public function calculateNormative(array $intersectArray)
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
            $calculateArray['kf'] = '1.0';

            $calculateArray['priceByMeter'] = round((float)($calculateArray['priceZone']) * (float)$calculateArray['priceLocal'] * (float)$calculateArray['kf'], 2);
            $calculateArray['priceTotal'] = round($calculateArray['priceByMeter'] * round($calculateArray['area']), 2);

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

    private function
    regulateLocals(array $locals)
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

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}