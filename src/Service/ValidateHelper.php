<?php


namespace App\Service;


use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\File as FileConstraint;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ValidateHelper
{
    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param UploadedFile $file
     * @return ConstraintViolationListInterface
     */
    public function validateNormativeXml(UploadedFile $file): ConstraintViolationListInterface
    {
        $errors = $this->validator->validate($file, [
            new FileConstraint([
                'maxSize' => '20240k',
                'mimeTypes' => [
                    'text/xml',
                ],
                'mimeTypesMessage' => 'Будь-ласка завантажте валідний файл (*.xml)',
                'maxSizeMessage' => 'Файл не повинен перевищувати 20Mb',
            ]),
        ]);
        return $errors;
    }

    /**
 * @param UploadedFile $file
 * @return ConstraintViolationListInterface
 */
    public function validateFile(UploadedFile $file): ConstraintViolationListInterface
    {
        $errors = $this->validator->validate($file, [
            new FileConstraint([
                'maxSize' => '1024k',
                'maxSizeMessage' => 'Файл не повинен перевищувати 1Mb',
            ]),
        ]);

        return $errors;
    }

    /**
     * @param string $cadNum
     * @return ConstraintViolationListInterface
     */
    public function validateCadNum(string $cadNum): ConstraintViolationListInterface
    {
        $errors = $this->validator->validate($cadNum, [
            new Assert\Regex([
                'pattern' => "/^[0-9]{10}:[0-9]{2}:[0-9]{3}:[0-9]{4}$/",
                'message' => 'Помилка! Кадастровий номер не відповідє шаблону!',
            ]),
        ]);

        return $errors;
    }


    /**
     * @param string $json
     * @return ConstraintViolationListInterface
     */

    public function validateJsonString(string $json): ConstraintViolationListInterface
    {
        $errors = $this->validator->validate($json, [
            new  Assert\Json([
                'message' => 'Будь-ласка завантажте валідний файл (*.json)',
            ]),
        ]);

        return $errors;
    }
}