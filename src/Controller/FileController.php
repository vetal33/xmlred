<?php

namespace App\Controller;

use App\Entity\File;
use App\Form\FileFormType;
use App\Repository\FileRepository;
use App\Repository\ParcelRepository;
use App\Repository\PurposeDirRepository;
use App\Service\ApiClient\EServicesClient;
use App\Service\JsonUploader;
use App\Service\NormativeXmlSaver;
use App\Service\NormativeXmlParser;
use App\Service\NormativeXmlValidator;
use App\Service\ParcelHandler;
use App\Service\ParcelXmlParser;
use App\Service\ParcelXmlSaver;
use App\Service\ValidateHelper;
use App\Service\XmlFileUploader;
use App\Service\XmlUploader;
use League\Flysystem\Exception;
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
     * @var ValidateHelper
     */
    private $validateHelper;
    /**
     * @var FileRepository
     */
    private $fileRepository;
    /**
     * @var EServicesClient
     */
    private $servicesClient;
    /**
     * @var NormativeXmlParser
     */
    private $normativeXmlParser;
    /**
     * @var NormativeXmlSaver
     */
    private $normativeXmlSaver;

    public function __construct(
        ValidateHelper $validateHelper,
        FileRepository $fileRepository,
        EServicesClient $servicesClient,
        NormativeXmlParser $normativeXmlParser,
        NormativeXmlSaver $normativeXmlSaver
    )
    {
        $this->validateHelper = $validateHelper;
        $this->fileRepository = $fileRepository;
        $this->servicesClient = $servicesClient;
        $this->normativeXmlParser = $normativeXmlParser;
        $this->normativeXmlSaver = $normativeXmlSaver;
    }

    /**
     * @Route("/", name="homepage", methods={"GET","POST"}, options={"expose"=true})
     * @param Request $request
     * @param XmlUploader $uploader
     *
     * @return Response
     */
    public function index(Request $request, XmlUploader $uploader): Response
    {
        $data = [];
        $file = new File;
        $form = $this->createForm(FileFormType::class, $file);

        if (!$request->isXmlHttpRequest()) {
            return $this->render('file/index.html.twig', ['fileForm' => $form->createView()]);
        }
        try {
            if ($request->request->get('xmlFile')) {
                $xmlObj = $uploader->getSimpleXML('test_normative.xml');

                if (!$xmlObj) {
                    $error = sprintf('Вибачте!, тестові дані не знайдено!');
                    return new JsonResponse($error, Response::HTTP_NOT_FOUND);
                };
                $origXmlName = 'test_normative.xml';
                $newXmlName = 'test_normative.xml';
            } else {
                /**@var UploadedFile $uploadedFile */
                $uploadedFile = $request->files->get('xmlFile');

                $errors = $this->validateHelper->validateNormativeXml($uploadedFile);
                if (0 != count($errors)) {
                    $data['errors'][] = $errors[0]->getMessage();
                    return new JsonResponse(json_encode($data), Response::HTTP_OK);
                }

                $uploader->upload($uploadedFile);
                $xmlObj = $uploader->getSimpleXML($uploader->getNewName());
                $origXmlName = $uploader->getOriginalName();
                $newXmlName = $uploader->getNewName();
            }
            if (!$xmlObj) {
                $data['errors'] = $uploader->getErrors();
                return new JsonResponse(json_encode($data), Response::HTTP_OK);
            }
            $parseXml = $this->normativeXmlParser->parse($xmlObj);
            if (!$parseXml) {
                $data['errors'] = $this->normativeXmlParser->getErrors();
                return new JsonResponse(json_encode($data), Response::HTTP_OK);
            }

            if ($this->isGranted('ROLE_USER')) {
                $this->normativeXmlSaver->toShape($parseXml);
            }
            $data = $this->normativeXmlSaver->toGeoJson($parseXml);

            $data['origXml'] = $xmlObj;
            $data['newXmlName'] = $newXmlName;
            $data['origXmlName'] = $origXmlName;
            $data['errors'] = [];

            return new JsonResponse(json_encode($data), Response::HTTP_OK);
        } catch (NotFoundHttpException $exception) {

            return new JsonResponse($exception->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (Exception $exception) {

            return $this->json(['message' => 'Виникла помилка, вибачте за незручності!'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Route("/loadParcels", name="loadParcels", methods={"POST"}, options={"expose"=true} )
     * @param ParcelRepository $parcelRepository
     * @param ParcelHandler $parcelHandler
     * @return JsonResponse
     */

    public function loadParcels(ParcelRepository $parcelRepository, ParcelHandler $parcelHandler)
    {
        if ($this->isGranted('ROLE_USER')) {
            try {
                $parcelsJson = [];
                $parcels = $parcelRepository->findBy(['userId' => $this->getUser()]);
                if ($parcels) {
                    $parcelsJson = $parcelHandler->convertToJson($parcels);
                }
                return new JsonResponse(json_encode($parcelsJson), Response::HTTP_OK);
            } catch
            (\Exception $exception) {
                return $this->json(['message' => 'Виникла помилка, вибачте за незручності!'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
        return new JsonResponse('', Response::HTTP_OK);
    }

    /**
     *
     * @Route("/load", name = "downloadShp", methods={"GET","POST"}, options={"expose"=true})
     * @param Request $request
     * @param NormativeXmlSaver $normativeXmlSaver
     * @return Response
     */

    public function downloadShp(Request $request): Response
    {
        if ($this->isGranted('ROLE_USER')) {
            if ($request->request->get('name') === '/load?name=test_normative.xml') {

                return new JsonResponse('Тестові дані неможливо скачати!', Response::HTTP_NOT_FOUND);
            }

            $fileName = $this->normativeXmlSaver->addToZip();
            $stream = new Stream($fileName);
            $response = new BinaryFileResponse($stream);
            clearstatcache(true, $fileName);

            return $response;
        }
        return $this->redirectToRoute('app_login');
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
                $simpleXmlFile = $uploader->getSimpleXML($fileName);

                if (!$simpleXmlFile) {
                    $error = sprintf('Вибачте!, файл "%s" не знайдено!', $fileName);
                    return new JsonResponse($error, Response::HTTP_NOT_FOUND);
                }

                $parseXml = $this->normativeXmlParser->parse($simpleXmlFile);

                if (!$parseXml) {
                    $data['errors'] = $this->normativeXmlParser->getErrors();
                    return new JsonResponse(json_encode($data), Response::HTTP_OK);
                }

                $parseJson = $this->normativeXmlSaver->toGeoJson($parseXml, false);

                $normativeXmlValidator->validateStructure($simpleXmlFile);
                if (!empty($normativeXmlValidator->getErrors())) {
                    $data['validate_errors'] = $normativeXmlValidator->getErrors();
                }

                $normativeXmlValidator->validateGeom($parseJson);


                return new JsonResponse(json_encode($data), Response::HTTP_OK);
            } catch (\Exception $exception) {
                return $this->json(['message' => 'Виникла помилка, вибачте за незручності!'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
        return new JsonResponse('Для виконання цієї дії потрібно зайти в систему або зареструватись!', Response::HTTP_FORBIDDEN);
    }

    /**
     * @Route("/import/json", name="importJson", methods={"POST"}, options={"expose"=true} )
     * @param Request $request
     * @param JsonUploader $jsonUploader
     * @return JsonResponse|Response
     */
    public function importJson(
        Request $request,
        JsonUploader $jsonUploader
    )
    {
        if ($this->isGranted('ROLE_USER')) {
            if ($request->isXmlHttpRequest()) {
                try {
                    /**@var UploadedFile $uploadedFile */
                    $uploadedFile = $request->files->get('jsonFile');
                    $errors = $this->validateHelper->validateFile($uploadedFile);

                    if (0 != count($errors)) {
                        $data['errors'][] = $errors[0]->getMessage();
                        return new JsonResponse(json_encode($data), Response::HTTP_OK);
                    }

                    $fileStr = file_get_contents($uploadedFile);
                    $errors = $this->validateHelper->validateJsonString($fileStr);

                    if (0 != count($errors)) {
                        $data['errors'][] = $errors[0]->getMessage();
                        return new JsonResponse(json_encode($data), Response::HTTP_OK);
                    }

                    $jsonUploader->upload($uploadedFile);
                    $wkt = $this->fileRepository->getGeomFromJsonAsWkt($fileStr);

                    $data['area'] = $this->fileRepository->calcArea($wkt);
                    $data['newFileName'] = $jsonUploader->getNewName();
                    $transformFeature = $this->fileRepository->transformFromSK63To3857($wkt);

                    if ($this->servicesClient->isConnect()) {
                        $centroid = $this->fileRepository->getCentroid($transformFeature);
                        $centroidArray = $this->fileRepository->wktPointToArray($centroid);
                        if ($pubData = $this->servicesClient->getParcelsInPoint(['point' => 'Point(' . implode(" ", $centroidArray) . ')'])) {
                            $data['pub'] = $pubData;
                        }
                    }

                    $wktTransform = $this->fileRepository->transformFeatureFromSC63to4326($wkt);
                    $data['wkt'] = $wkt;
                    $jsonTransform = $this->fileRepository->getJsonFromWkt($wktTransform);
                    $data['json'] = $jsonTransform;
                    $data['errors'] = [];

                    return new JsonResponse(json_encode($data), Response::HTTP_OK);

                } catch (\Exception $exception) {
                    return $this->json(['message' => 'Виникла помилка, вибачте за незручності!'], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }
        }
        return new JsonResponse('Для виконання цієї дії потрібно зайти в систему або зареструватись!', Response::HTTP_FORBIDDEN);
    }

    /**
     * @Route("/import/xmlFile", name="import_xmlFile", methods={"POST"}, options={"expose"=true})
     * @param Request $request
     * @param XmlFileUploader $xmlFileUploader
     * @param ParcelXmlParser $parcelXmlParser
     * @param ParcelXmlSaver $parcelXmlSaver
     * @return JsonResponse
     */
    public function importXml(
        Request $request,
        XmlFileUploader $xmlFileUploader,
        ParcelXmlParser $parcelXmlParser,
        ParcelXmlSaver $parcelXmlSaver)
    {
        if ($this->isGranted('ROLE_USER')) {
            if ($request->isXmlHttpRequest()) {
                try {
                    /**@var UploadedFile $uploadedFile */
                    $uploadedFile = $request->files->get('xmlFile');
                    $errors = $this->validateHelper->validateXmlFile($uploadedFile);
                    if (0 != count($errors)) {
                        $data['errors'][] = $errors[0]->getMessage();
                        return new JsonResponse(json_encode($data), Response::HTTP_OK);
                    }

                    $xmlFileUploader->upload($uploadedFile);
                    $xmlObj = $xmlFileUploader->getSimpleXML($xmlFileUploader->getNewName());
                    $parseXml = $parcelXmlParser->parse($xmlObj);

                    if (!$parseXml) {
                        $data['errors'] = $parcelXmlParser->getErrors();
                        return new JsonResponse(json_encode($data), Response::HTTP_OK);
                    }

                    $data = $parcelXmlSaver->toGeoJson($parseXml);
                    $dataSK63 = $parcelXmlSaver->toGeoJson($parseXml, false);
                    $wkt = $this->fileRepository->getGeomFromJsonAsWkt($dataSK63['parcelXml']['coordinates']);

                    $data['area'] = $this->fileRepository->calcArea($wkt);
                    $data['newFileName'] = $xmlFileUploader->getNewName();
                    $data['wkt'] = $wkt;
                    $data['json'] = $data['parcelXml']['coordinates'];
                    $transformFeature = $this->fileRepository->transformFromSK63To3857($wkt);

                    if ($this->servicesClient->isConnect()) {
                        $centroid = $this->fileRepository->getCentroid($transformFeature);
                        $centroidArray = $this->fileRepository->wktPointToArray($centroid);
                        if ($pubData = $this->servicesClient->getParcelsInPoint(['point' => 'Point(' . implode(" ", $centroidArray) . ')'])) {
                            $data['pub'] = $pubData;
                        }
                    }

                    $data['errors'] = [];

                    return new JsonResponse(json_encode($data), Response::HTTP_OK);
                } catch (\Exception $exception) {
                    return $this->json(['message' => 'Виникла помилка, вибачте за незручності!'], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }
        }
        return new JsonResponse('Для виконання цієї дії потрібно зайти в систему або зареструватись!', Response::HTTP_FORBIDDEN);
    }

    /**
     * @Route("calculate", name="calculateNormative", methods={"POST"}, options={"expose"=true} )
     * @param Request $request
     * @param XmlUploader $uploader
     * @param NormativeXmlSaver $normativeXmlSaver
     * @param FileRepository $fileRepository
     * @param EServicesClient $servicesClient
     * @param ParcelRepository $parcelRepository
     * @param ParcelHandler $parcelHandler
     * @param PurposeDirRepository $purposeDirRepository
     * @return JsonResponse
     */

    public function calculateNormative(
        Request $request,
        XmlUploader $uploader,
        NormativeXmlSaver $normativeXmlSaver,
        FileRepository $fileRepository,
        EServicesClient $servicesClient,
        ParcelRepository $parcelRepository,
        ParcelHandler $parcelHandler,
        PurposeDirRepository $purposeDirRepository
    )
    {
        if ($this->isGranted('ROLE_USER')) {
            try {
                $data = [];
                $fileName = $request->request->get('fileName');
                $feature = $request->request->get('feature');
                $year = (integer)$request->request->get('normativeYear');


                if ($feature) {
                    $result = $fileRepository->isValid($feature);
                    if (!$result) {
                        $error = 'Вибачте!, геометрія ділянки не валідна!';
                        return new JsonResponse($error, Response::HTTP_NOT_FOUND);
                    }
                } else {
                    $cadNum = $request->request->get('cadNum');
                    $parcel = $parcelRepository->findOneBy(['cadNum' => $cadNum]);

                    if (!$parcel) {
                        $error = sprintf('Вибачте!, ділянки з кадастровим номером %s не знайдено!', $cadNum);
                        return new JsonResponse($error, Response::HTTP_NOT_FOUND);
                    }

                    $feature = $parcel->getGeom()->getOriginalGeom();
                }

                $simpleXmlFile = $uploader->getSimpleXML($fileName);

                if (!$simpleXmlFile) {
                    $error = sprintf('Вибачте!, файл "%s" не знайдено!', $fileName);
                    return new JsonResponse($error, Response::HTTP_NOT_FOUND);
                }

                $parseXml = $this->normativeXmlParser->parse($simpleXmlFile);

                if (!$parseXml) {
                    $data['errors'] = $this->normativeXmlParser->getErrors();
                    return new JsonResponse(json_encode($data), Response::HTTP_OK);
                }

                $data = $normativeXmlSaver->toGeoJson($parseXml, false);
                $resultIntersect = $normativeXmlSaver->intersect($data, $feature);

                if (!$resultIntersect) {
                    $data['errors'] = $normativeXmlSaver->getErrors();

                    return new JsonResponse(json_encode($data), Response::HTTP_OK);
                }

                if ($normativeXmlSaver->getErrors()) {
                    $resultIntersect['errors'] = $normativeXmlSaver->getErrors();
                }

                $resultIntersect['area'] = $fileRepository->calcArea($feature);
                $transformFeature = $fileRepository->transformFromSK63To3857($feature);

                if ($servicesClient->isConnect()) {
                    $centroid = $fileRepository->getCentroid($transformFeature);
                    $centroidArray = $fileRepository->wktPointToArray($centroid);
                    if ($pubData = $servicesClient->getParcelsInPoint(['point' => 'Point(' . implode(" ", $centroidArray) . ')'])) {
                        $resultIntersect['pub'] = $pubData;

                    }
                }
                $resultIntersect['cmn'] = $parseXml['boundary']['Cnm'];

                $calculate = $parcelHandler->calculateNormative($resultIntersect, $year);
                if (!$calculate) {
                    $data['errors'] = $parcelHandler->getErrors();

                    return new JsonResponse(json_encode($data), Response::HTTP_OK);
                }
                $resultIntersect['calculate'] = $calculate;

                if (array_key_exists('local', $resultIntersect)) {
                    $resultIntersect['local'] = $parcelHandler->addMarkerToLocals($resultIntersect['local']);
                }

                return new JsonResponse(json_encode($resultIntersect), Response::HTTP_OK);
            } catch (\Exception $exception) {
                return $this->json(['message' => 'Виникла помилка, вибачте за незручності!'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
        return new JsonResponse('Для виконання цієї дії потрібно зайти в систему або зареструватись!', Response::HTTP_FORBIDDEN);
    }
}
