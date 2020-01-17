<?php


namespace App\Service;


use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\File as FileConstraint;
use Symfony\Component\Validator\ConstraintViolationListInterface;
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

    /**
     * @param UploadedFile $file
     * @return ConstraintViolationListInterface
     */
    public function validateNormativeXml(UploadedFile $file): ConstraintViolationListInterface
    {
        $errors = $this->validator->validate($file, [
            new FileConstraint([
                'maxSize' => '100024k',
                'mimeTypes' => [
                    'text/xml',
                ],
                'mimeTypesMessage' => 'Будь-ласка завантажте валідний файл (*.xml)',
                'maxSizeMessage' => 'Файл не повинен перевищувати 10Mb',
            ]),
        ]);
        return $errors;
    }
}