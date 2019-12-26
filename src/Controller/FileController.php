<?php

namespace App\Controller;

use App\Entity\File;
use App\Form\FileFormType;
use App\Service\Uploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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
     * @return Response
     * @throws \Exception
     */
    public function index(Request $request, EntityManagerInterface $entityManager, Uploader $uploader): Response
    {
        $file = new File;
        $form = $this->createForm(FileFormType::class, $file);
        $form->handleRequest($request);

        //$uploadedFile = $form['xmlFile']->getData();


        if ($request->isXmlHttpRequest()) {
            /**@var UploadedFile $uploadedFile */
            $uploadedFile = $request->files->get('xmlFile');

            if ($uploadedFile) {
                $newFilename = $uploader->uploadXML($uploadedFile);
                $xmlObj = $uploader->getXml($newFilename);
                if ($xmlObj) {
                    $file->setXmlFileName($newFilename);
                    $file->setAddDate(new \DateTime());
                    $entityManager->persist($file);
                    $entityManager->flush();
                    return new JsonResponse(json_encode($xmlObj), Response::HTTP_OK);
                }


            }
        }

        return $this->render('file/index.html.twig', [
            'fileForm' => $form->createView(),
            'controller_name' => 'FileController',
        ]);
    }
}
