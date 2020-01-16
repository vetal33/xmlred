<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class AccountController extends BaseController
{
    /**
     * @Route("/account", name="account")
     * @param LoggerInterface $logger
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index(LoggerInterface $logger)
    {
        $logger->debug('dfsdfdf' . $this->getUser()->getEmail());
        //dd();
        return $this->render('account/index.html.twig', [
            'controller_name' => 'AccountController',
        ]);
    }
}
