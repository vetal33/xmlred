<?php

namespace App\Controller;

use App\Entity\Geom;
use App\Entity\Parcel;
use App\Entity\User;
use App\Form\ParcelType;
use App\Repository\ParcelRepository;
use App\Service\JsonUploader;
use App\Service\ParcelHandler;
use App\Service\ValidateHelper;
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
     * @return JsonResponse|Response
     */
    public function save(
        Request $request,
        ValidateHelper $validateHelper,
        JsonUploader $jsonUploader,
        ParcelRepository $parcelRepository,
        ParcelHandler $parcelHandler): Response
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

                    $fileStr = $jsonUploader->loadFileAsStr($newFileName);
                    if (!$fileStr) {
                        $error = sprintf('Файл з ділянкою не знайдено!');
                        return new JsonResponse($error, Response::HTTP_NOT_FOUND);
                    }

                    $errors = $validateHelper->validateJsonString($fileStr);

                    if (0 != count($errors)) {
                        $data['errors'][] = $errors[0]->getMessage();
                        return new JsonResponse(json_encode($data), Response::HTTP_OK);
                    }

                    if ($parcelRepository->findOneBy(['cadNum' => $cadNum, 'userId' => $this->getUser()])) {
                        $data['errors'][] = sprintf('Ділянка з кадастровим номером %s вже існує', $cadNum);
                        return new JsonResponse(json_encode($data), Response::HTTP_OK);
                    }

                    $wkt = $parcelRepository->getGeomFromJsonAsWkt($fileStr);
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

                    $parcelHandler->removeFile($newFileName);
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
     * @Route("/new", name="parcel_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $parcel = new Parcel();
        $form = $this->createForm(ParcelType::class, $parcel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($parcel);
            $entityManager->flush();

            return $this->redirectToRoute('parcel_index');
        }

        return $this->render('parcel/new.html.twig', [
            'parcel' => $parcel,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="parcel_show", methods={"GET"})
     */
    public function show(Parcel $parcel): Response
    {
        return $this->render('parcel/show.html.twig', [
            'parcel' => $parcel,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="parcel_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Parcel $parcel): Response
    {
        $form = $this->createForm(ParcelType::class, $parcel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('parcel_index');
        }

        return $this->render('parcel/edit.html.twig', [
            'parcel' => $parcel,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="parcel_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Parcel $parcel): Response
    {
        if ($this->isCsrfTokenValid('delete' . $parcel->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($parcel);
            $entityManager->flush();
        }

        return $this->redirectToRoute('parcel_index');
    }
}
