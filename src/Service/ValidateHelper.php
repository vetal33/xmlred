<?php


namespace App\Service;


use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\File as FileConstraint;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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

    public function validateNormativeXml(UploadedFile $file): ConstraintViolationList
    {
        $errors = $this->validator->validate($file, [
            new FileConstraint([
                'maxSize' => '100024k',
                'mimeTypes' => [
                    'text/xml',
                ],
                'mimeTypesMessage' => 'Будь-ласка завантажте валідний файл',
                'maxSizeMessage' => 'Файл не повинен перевищувати 10Mb',
            ]),
        ]);
        return $errors;
    }

}