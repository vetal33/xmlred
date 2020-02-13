<?php

namespace App\Controller;

use App\Entity\File;
use App\Form\FileFormType;
use App\Repository\FileRepository;
use App\Service\JsonUploader;
use App\Service\NormativeXmlSaver;
use App\Service\NormativeXmlParser;
use App\Service\NormativeXmlValidator;
use App\Service\Uploader;
use App\Service\ValidateHelper;
use App\Service\XmlUploader;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Stream;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;


/**
 * Class FileController
 * @package App\Controller
 */
class FileController extends AbstractController
{
    /**
     * @Route("/", name="homepage", methods={"GET","POST"}, options={"expose"=true})
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param XmlUploader $uploader
     * @param NormativeXmlParser $normativeXmlParser
     * @param NormativeXmlSaver $normativeXmlSaver
     * @param ValidateHelper $validateHelper
     * @return Response
     * @throws \Exception
     */
    public function index(Request $request,
                          EntityManagerInterface $entityManager,
                          XmlUploader $uploader,
                          NormativeXmlParser $normativeXmlParser,
                          NormativeXmlSaver $normativeXmlSaver,
                          ValidateHelper $validateHelper): Response
    {
        $data = [];
        $file = new File;

        $form = $this->createForm(FileFormType::class, $file);

        if ($request->isXmlHttpRequest()) {

            try {
                /**@var UploadedFile $uploadedFile */
                $uploadedFile = $request->files->get('xmlFile');
                $errors = $validateHelper->validateNormativeXml($uploadedFile);

                if (0 != count($errors)) {
                    $data['errors'][] = $errors[0]->getMessage();
                    return new JsonResponse(json_encode($data), Response::HTTP_OK);
                }

                $uploader->upload($uploadedFile);
                $xmlObj = $uploader->getSimpleXML($uploader->getNewName());

                if (!$xmlObj) {
                    $data['errors'] = $uploader->getErrors();
                    return new JsonResponse(json_encode($data), Response::HTTP_OK);
                }
                $parseXml = $normativeXmlParser->parse($xmlObj);

                if (!$parseXml) {
                    $data['errors'] = $normativeXmlParser->getErrors();
                    return new JsonResponse(json_encode($data), Response::HTTP_OK);
                }

                if ($this->isGranted('ROLE_USER')) {
                    $result = $normativeXmlSaver->toShape($parseXml);
                }

                $data = $normativeXmlSaver->toGeoJson($parseXml);

                $data['origXml'] = $xmlObj;
                $data['origXmlName'] = $uploader->getOriginalName();
                $data['newXmlName'] = $uploader->getNewName();
                $data['errors'] = [];
                /* $file->setXmlFileName($newFilename);
                 $file->setAddDate(new \DateTime());
                 $file->setXmlOriginalName($uploader->getOriginalName());
                 $entityManager->persist($file);
                 $entityManager->flush();*/

                return new JsonResponse(json_encode($data), Response::HTTP_OK);
            } catch (NotFoundHttpException $exception) {

                return new JsonResponse($exception->getMessage(), Response::HTTP_NOT_FOUND);
            } catch (Exception $exception) {

                return $this->json(['message' => 'Виникла помилка, вибачте за незручності!'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        return $this->render('file/index.html.twig', [
            'fileForm' => $form->createView(),
            'controller_name' => 'FileController',
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @Route("/load",name = "downloadShp", methods={"GET","POST"}, options={"expose"=true})
     * @param XmlUploader $uploader
     * @param Request $request
     * @param NormativeXmlSaver $normativeXmlSaver
     * @return Response
     */

    public function downloadShp(XmlUploader $uploader, Request $request, NormativeXmlSaver $normativeXmlSaver): Response
    {
        $name = $request->query->get('name');
        $fileName = $normativeXmlSaver->addToZip($name);

        if (!$fileName) {
            die;
        }

        $stream = new Stream($fileName);
        $response = new BinaryFileResponse($stream);
        clearstatcache(true, $fileName);

        /*        $response = new StreamedResponse(function () use ($uploader) {
                    $outputStream = fopen('php://output', 'wb');
                    $fileStream = $uploader->download();
                    dump($fileStream);
                    stream_copy_to_stream($fileStream, $outputStream);
                });
                $response->headers->set('Content-Type', 'application/zip');

                $disposition = HeaderUtils::makeDisposition(
                    HeaderUtils::DISPOSITION_ATTACHMENT,
                    'test.zip'
                );
                $response->headers->set('Content-Disposition', $disposition);*/

        return $response;
    }

    /**
     * @Route("/verify", name="verifyXml", methods={"POST"}, options={"expose"=true})
     * @param Request $request
     * @param XmlUploader $uploader
     * @param NormativeXmlValidator $normativeXmlValidator
     * @return JsonResponse
     */
    public function verifyXml(Request $request, XmlUploader $uploader, NormativeXmlValidator $normativeXmlValidator)
    {
        if ($this->isGranted('ROLE_USER')) {
            try {
                $data = [];
                $fileName = $request->request->get('fileName');
                $file = $uploader->getSimpleXML($fileName);

                if (!$file) {
                    $error = sprintf('Вибачте!, файл "%s" не знайдено!', $fileName);
                    return new JsonResponse($error, Response::HTTP_NOT_FOUND);
                }

                $normativeXmlValidator->validate($file);
                if (!empty($normativeXmlValidator->getErrors())) {
                    $data['validate_errors'] = $normativeXmlValidator->getErrors();
                }

                return new JsonResponse(json_encode($data), Response::HTTP_OK);
            } catch (\Exception $exception) {
                return $this->json(['message' => 'Виникла помилка, вибачте за незручності!'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
        return new JsonResponse('Для виконання цієї дії потрібно зайти в систему або зареструватись!', Response::HTTP_FORBIDDEN);
    }

    /**
     * @Route("/import/json", name="impontJson", methods={"POST"}, options={"expose"=true} )
     * @param Request $request
     * @param ValidateHelper $validateHelper
     * @param JsonUploader $jsonUploader
     * @param FileRepository $fileRepository
     * @return JsonResponse|Response
     */
    public function importJson(
        Request $request,
        ValidateHelper $validateHelper,
        JsonUploader $jsonUploader,
        FileRepository $fileRepository
    )
    {
        if ($request->isXmlHttpRequest()) {
            try {

                /**@var UploadedFile $uploadedFile */
                $uploadedFile = $request->files->get('jsonFile');
                $errors = $validateHelper->validateFile($uploadedFile);

                if (0 != count($errors)) {
                    $data['errors'][] = $errors[0]->getMessage();
                    return new JsonResponse(json_encode($data), Response::HTTP_OK);
                }

                $fileStr = file_get_contents($uploadedFile);
                $errors = $validateHelper->validateJsonString($fileStr);

                if (0 != count($errors)) {
                    $data['errors'][] = $errors[0]->getMessage();
                    return new JsonResponse(json_encode($data), Response::HTTP_OK);
                }

                $jsonUploader->upload($uploadedFile);
                $wkt = $fileRepository->getGeomFromJsonAsWkt($fileStr);
                dump($wkt);
                $wktTransform = $fileRepository->transformFeatureFromSC63to4326($wkt);
                dump($wktTransform);
                $data['wkt'] = $wkt;
                $jsonTransform = $fileRepository->getJsonFromWkt($wktTransform);
                $data['json'] = $jsonTransform;

                return new JsonResponse(json_encode($data), Response::HTTP_OK);

            } catch (\Exception $exception) {
                return $this->json(['message' => 'Виникла помилка, вибачте за незручності!'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
        return $this->json(['message' => 'Заборонений доступ!'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * @Route("calculate", name="calculateNormative", methods={"POST"}, options={"expose"=true} )
     * @param Request $request
     * @param XmlUploader $uploader
     * @param NormativeXmlParser $normativeXmlParser
     * @param NormativeXmlSaver $normativeXmlSaver
     * @param FileRepository $fileRepository
     * @return JsonResponse
     */

    public function calculateNormative(
        Request $request,
        XmlUploader $uploader,
        NormativeXmlParser $normativeXmlParser,
        NormativeXmlSaver $normativeXmlSaver,
        FileRepository $fileRepository)
    {
        if ($this->isGranted('ROLE_USER')) {
            try {
                $data = [];
                $fileName = $request->request->get('fileName');
                $feature = $request->request->get('feature');
                dump($feature);

                $result = $fileRepository->isValid($feature);
                dump($result);
                if (!$result) {
                    $error = 'Вибачте!, геометрія ділянки не валідна!';
                    return new JsonResponse($error, Response::HTTP_NOT_FOUND);
                }

                $file = $uploader->getSimpleXML($fileName);

                if (!$file) {
                    $error = sprintf('Вибачте!, файл "%s" не знайдено!', $fileName);
                    return new JsonResponse($error, Response::HTTP_NOT_FOUND);
                }

                $parseXml = $normativeXmlParser->parse($file);
                dump($parseXml);

                if (!$parseXml) {
                    $data['errors'] = $normativeXmlParser->getErrors();
                    return new JsonResponse(json_encode($data), Response::HTTP_OK);
                }

                $data = $normativeXmlSaver->toGeoJson($parseXml, false);
                $resultIntersect = $normativeXmlSaver->intersect($data, $feature);

                if (!$resultIntersect) {
                    $data['errors'] = $normativeXmlSaver->getErrors();

                    return new JsonResponse(json_encode($data), Response::HTTP_OK);
                }
                $resultIntersect['area'] = $fileRepository->calcArea($feature);

                return new JsonResponse(json_encode($resultIntersect), Response::HTTP_OK);
            } catch (\Exception $exception) {

                return $this->json(['message' => 'Виникла помилка, вибачте за незручності!'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
        return new JsonResponse('Для виконання цієї дії потрібно зайти в систему або зареструватись!', Response::HTTP_FORBIDDEN);

    }
}
