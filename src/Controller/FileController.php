<?php

namespace App\Controller;

use App\Entity\File;
use App\Form\FileFormType;
use App\Service\NormativeXmlHandler;
use App\Service\ParserXmlNormative;
use App\Service\Uploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Shapefile\Shapefile;
use Shapefile\ShapefileReader;
use Shapefile\ShapefileException;

/**
 * Class FileController
 * @package App\Controller
 */
class FileController extends AbstractController
{

    /**
     * @Route("/", name="home", methods={"GET","POST"}, options={"expose"=true})
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param Uploader $uploader
     * @param ParserXmlNormative $parserXmlNormative
     * @param NormativeXmlHandler $normativeXmlHandler
     * @return Response
     * @throws \Exception
     */
    public function index(Request $request, EntityManagerInterface $entityManager, Uploader $uploader, ParserXmlNormative $parserXmlNormative, NormativeXmlHandler $normativeXmlHandler): Response
    {
        $file = new File;
        $form = $this->createForm(FileFormType::class, $file);
        $form->handleRequest($request);

        if ($request->isXmlHttpRequest()) {
            /**@var UploadedFile $uploadedFile */
            $uploadedFile = $request->files->get('xmlFile');

            if ($uploadedFile) {
                $newFilename = $uploader->uploadXML($uploadedFile);
                $xmlObj = $uploader->getSimpleXML($newFilename);

                if ($xmlObj) {
                    $array = $parserXmlNormative->parse($xmlObj);
                    $boundary = $parserXmlNormative->parceDataXml($array);

                    if ($boundary['boundary']) {
                        $normativeXmlHandler->toShape($boundary);
                        $file->setXmlFileName($newFilename);
                        $file->setAddDate(new \DateTime());
                        $entityManager->persist($file);
                        $entityManager->flush();

                    }

                    return new JsonResponse(json_encode($xmlObj), Response::HTTP_OK);
                }
            }
        }

        return $this->render('file/index.html.twig', [
            'fileForm' => $form->createView(),
            'controller_name' => 'FileController',
        ]);
    }

    /**
     * @Route("/load")
     */

    public function openShp(NormativeXmlHandler $normativeXmlHandler): Response
    {
        $normativeXmlHandler->toShape();
        die;
    }
}
