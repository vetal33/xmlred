<?php

namespace App\Controller;

use App\Entity\Geom;
use App\Entity\Parcel;
use App\Entity\User;
use App\Repository\ParcelRepository;
use App\Service\FactoryMethod\ParcelParserFactory;
use App\Service\JsonUploader;
use App\Service\ParcelHandler;
use App\Service\ParcelXmlParser;
use App\Service\ValidateHelper;
use FOS\ElasticaBundle\Manager\RepositoryManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/parcel")
 */
class ParcelController extends AbstractController
{
    /**
     * @Route("/", name="parcel_index", methods={"GET"})
     * @param ParcelRepository $parcelRepository
     * @return Response
     */
    public function index(ParcelRepository $parcelRepository): Response
    {
        return $this->render('parcel/index.html.twig', [
            'parcels' => $parcelRepository->findAll(),
        ]);
    }

    /**
     * @Route("/save", name="parcel_save", methods={"POST"}, options={"expose"=true})
     * @param Request $request
     * @param ValidateHelper $validateHelper
     * @param JsonUploader $jsonUploader
     * @param ParcelRepository $parcelRepository
     * @param ParcelHandler $parcelHandler
     * @param ParcelXmlParser $parcelXmlParser
     * @param ParcelParserFactory $parcelParserFactory
     * @return JsonResponse|Response
     */
    public function save(
        Request $request,
        ValidateHelper $validateHelper,
        JsonUploader $jsonUploader,
        ParcelRepository $parcelRepository,
        ParcelHandler $parcelHandler,
        ParcelXmlParser $parcelXmlParser,
        ParcelParserFactory $parcelParserFactory
    ): Response
    {
        if ($this->isGranted('ROLE_USER')) {
            if ($request->isXmlHttpRequest()) {
                try {
                    $cadNum = $request->request->get('cadNum');
                    $newFileName = $request->request->get('newFileName');
                    $purpose = $request->request->get('purpose');

                    $errors = $validateHelper->validateCadNum($cadNum);

                    if (0 !== count($errors)) {
                        $data['errors'][] = $errors[0]->getMessage();
                        return new JsonResponse(json_encode($data), Response::HTTP_OK);
                    }

                    if ($parcelRepository->findOneBy(['cadNum' => $cadNum, 'userId' => $this->getUser()])) {
                        $data['errors'][] = sprintf('Ділянка з кадастровим номером %s вже існує', $cadNum);
                        return new JsonResponse(json_encode($data), Response::HTTP_OK);
                    }

                    $parcelParser = $parcelParserFactory->createParcelParser($newFileName);
                    $wkt = $parcelParser->getWktFromFileByName($newFileName);
                    if (!$wkt) {
                        return new JsonResponse($parcelXmlParser->getErrors()[0], Response::HTTP_NOT_FOUND);
                    }

                    $area = $parcelRepository->calcArea($wkt);
                    $entityManager = $this->getDoctrine()->getManager();

                    $parcel = new Parcel();

                    /** @var User $user */
                    $user = $this->getUser();

                    $geom = new Geom();
                    $geom->setOriginalGeom($wkt);
                    $parcel
                        ->setCadNum($cadNum)
                        ->setArea($area)
                        ->setUserId($user)
                        ->setGeom($geom)
                        ->setUse(htmlspecialchars($purpose));

                    $entityManager->persist($parcel);
                    $entityManager->flush();

                    $parcelsJson = [];
                    $parcels = $parcelRepository->findBy(['userId' => $this->getUser()]);
                    if ($parcels) {
                        $parcelsJson = $parcelHandler->convertToJson($parcels);
                    }

                    //$parcelHandler->removeFile($newFileName);
                    $data['parcelsJson'] = $parcelsJson;
                    $data['errors'] = [];
                    $data['msg'] = sprintf('Ділянка %s успішно збережена!', $cadNum);
                    return new JsonResponse(json_encode($data), Response::HTTP_OK);

                } catch (\Exception $exception) {
                    return $this->json(['message' => 'Виникла помилка, вибачте за незручності!'], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }
        }
        return new JsonResponse('Для виконання цієї дії потрібно зайти в систему або зареструватись!', Response::HTTP_FORBIDDEN);
    }

    /**
     * @Route("/search", name="parcel_search", methods={"POST"}, options={"expose"=true})
     * @param Request $request
     * @param ParcelRepository $parcelRepository
     * @param ParcelHandler $parcelHandler
     * @return JsonResponse
     */

    public function search(Request $request, ParcelRepository $parcelRepository, ParcelHandler $parcelHandler)
    {
        if ($this->isGranted('ROLE_USER')) {
            if ($request->isXmlHttpRequest()) {
                try {
                    $resArray = [];
                    $searchText = $request->request->get('search');
                    $parcels = $parcelRepository->search($searchText);
                    if ($parcels) {
                        $resArray = $parcelHandler->convertToJsonWithoutGeom($parcels);
                    }

                    return new JsonResponse(json_encode($resArray), Response::HTTP_OK);
                } catch (\Exception $exception) {
                    return $this->json(['message' => 'Виникла помилка, вибачте за незручності!'], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }
        }
        return new JsonResponse('Для виконання цієї дії потрібно зайти в систему або зареструватись!', Response::HTTP_FORBIDDEN);
    }

    /**
     * @Route("/delete", name="parcel_delete", methods={"POST"}, options={"expose"=true})
     * @param Request $request
     * @param ParcelRepository $parcelRepository
     * @param ParcelHandler $parcelHandler
     * @return Response
     */
    public function delete(Request $request, ParcelRepository $parcelRepository, ParcelHandler $parcelHandler): Response
    {
        if ($this->isGranted('ROLE_USER')) {
            if ($request->isXmlHttpRequest()) {
                try {
                    $cadNum = $request->request->get('cadNum');

                    /** @var Parcel $parcel */
                    $parcel = $parcelRepository->findOneBy(['cadNum' => $cadNum, 'userId' => $this->getUser()]);

                    if (!$parcel) {
                        $error = sprintf('Ділянка з кадастровим номером %s не знайдено!', $cadNum);
                        return new JsonResponse($error, Response::HTTP_NOT_FOUND);
                    }

                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->remove($parcel);
                    $entityManager->flush();

                    $parcelsJson = [];
                    $parcels = $parcelRepository->findBy(['userId' => $this->getUser()]);
                    if ($parcels) {
                        $parcelsJson = $parcelHandler->convertToJson($parcels);
                    }

                    //$parcelHandler->removeFile($newFileName);
                    $data['parcelsJson'] = $parcelsJson;
                    $data['errors'] = [];
                    $data['msg'] = sprintf('Ділянка %s успішно видалена!', $cadNum);

                    return new JsonResponse(json_encode($data), Response::HTTP_OK);
                } catch (\Exception $exception) {
                    return $this->json(['message' => 'Виникла помилка, вибачте за незручності!'], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }
        }
        return new JsonResponse('Для виконання цієї дії потрібно зайти в систему або зареструватись!', Response::HTTP_FORBIDDEN);
    }
}
