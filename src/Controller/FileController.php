<?php

namespace App\Controller;

use App\Entity\File;
use App\Form\FileFormType;
use App\Service\NormativeXmlSaver;
use App\Service\NormativeXmlParser;
use App\Service\Uploader;
use App\Service\ValidateHelper;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
     * @param Uploader $uploader
     * @param NormativeXmlParser $normativeXmlParser
     * @param NormativeXmlSaver $normativeXmlSaver
     * @param ValidateHelper $validateHelper
     * @return Response
     * @throws \Exception
     */
    public function index(Request $request,
                          EntityManagerInterface $entityManager,
                          Uploader $uploader, NormativeXmlParser
                          $normativeXmlParser, NormativeXmlSaver
                          $normativeXmlSaver, ValidateHelper
                          $validateHelper): Response
    {
        $data = [];
        $file = new File;

        $form = $this->createForm(FileFormType::class, $file);

        if ($request->isXmlHttpRequest()) {

            /**@var UploadedFile $uploadedFile */
            $uploadedFile = $request->files->get('xmlFile');
            $errors = $validateHelper->validateNormativeXml($uploadedFile);

            if (0 != count($errors)) {
                $data['errors'][] = $errors[0]->getMessage();
                return new JsonResponse(json_encode($data), Response::HTTP_OK);
            }

            $uploader->uploadXML($uploadedFile);
            $xmlObj = $uploader->getSimpleXML($uploader->getNewNameFile());

            if (!$xmlObj) {
                $data['errors'] = $uploader->getErrors();
                return new JsonResponse(json_encode($data), Response::HTTP_OK);
            }

            $parseXml = $normativeXmlParser->parse($xmlObj);

            if (!$parseXml) {
                $data['errors'] = $normativeXmlParser->getErrors();
                return new JsonResponse(json_encode($data), Response::HTTP_OK);
            }

            if($this->isGranted('ROLE_USER')) {
                $result = $normativeXmlSaver->toShape($parseXml);
            }


            $data = $normativeXmlSaver->toGeoJson($parseXml);

            $data['origXml'] = $xmlObj;
            $data['origXmlName'] = $uploader->getOriginalName();
            $data['newXmlName'] = $uploader->getNewNameFile();
            $data['errors'] = [];
            /* $file->setXmlFileName($newFilename);
             $file->setAddDate(new \DateTime());
             $file->setXmlOriginalName($uploader->getOriginalName());
             $entityManager->persist($file);
             $entityManager->flush();*/

            return new JsonResponse(json_encode($data), Response::HTTP_OK);
        }

        return $this->render('file/index.html.twig', [
            'fileForm' => $form->createView(),
            'controller_name' => 'FileController',
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @Route("/load",name = "downloadShp", methods={"GET","POST"}, options={"expose"=true})
     * @param Uploader $uploader
     * @param Request $request
     * @param NormativeXmlParser $normativeXmlParser
     * @param NormativeXmlSaver $normativeXmlSaver
     * @return Response
     */

    public function downloadShp(Uploader $uploader, Request $request, NormativeXmlParser $normativeXmlParser, NormativeXmlSaver $normativeXmlSaver): Response
    {
        //$this->denyAccessUnlessGranted('ROLE_USER');

        $name = $request->query->get('name');
        $xmlObj = $uploader->getSimpleXML($name);
        $parseXml = $normativeXmlParser->parse($xmlObj);
       // dump($xmlObj);
        dump($parseXml);
        $result = $normativeXmlSaver->toShape($parseXml);
        dump($result);

        if(!$result) {
            die;
        }


        //die;


        $response = new StreamedResponse(function () use ($uploader) {
            $outputStream = fopen('php://output', 'wb');
            $fileStream = $uploader->download();
            stream_copy_to_stream($fileStream, $outputStream);
        });
        $response->headers->set('Content-Type', 'text/plain');

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            'filename.txt'
        );
        $response->headers->set('Content-Disposition', $disposition);

        return $response;

    }
}
