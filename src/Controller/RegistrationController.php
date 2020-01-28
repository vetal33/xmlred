<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\LoginFormAuthenticator;
use App\Service\CodeGenerator;
use App\Service\Mailer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;

class RegistrationController extends AbstractController
{
    /**
     * @Route("/register", name="app_register")
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param GuardAuthenticatorHandler $guardHandler
     * @param LoginFormAuthenticator $authenticator
     * @param Mailer $mailer
     * @param CodeGenerator $codeGenerator
     * @return Response
     */
    public function register(
        Request $request,
        UserPasswordEncoderInterface $passwordEncoder,
        GuardAuthenticatorHandler $guardHandler,
        LoginFormAuthenticator $authenticator,
        Mailer $mailer,
        CodeGenerator $codeGenerator): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
             // encode the plain password
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $user->setConfirmationCode($codeGenerator->getConfirmationCode());
            $user->setFolderName(preg_replace('/[^\p{L}\p{N}\s]/u', '', $user->getEmail()));

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $mailer->sendConfirmationMessage($user);
            return $this->render('registration/account_request.html.twig', [
                'user' => $user,
            ]);

            // do anything else you need here, like send an email

/*            return $guardHandler->authenticateUserAndHandleSuccess(
                $user,
                $request,
                $authenticator,
                'main' // firewall name in security.yaml
            );*/
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/confirm/{code}", name="register_email_confirmation")
     * @param UserRepository $userRepository
     * @param string $code
     * @return Response
     */
    public function confirmEmail(UserRepository $userRepository, string $code)
    {
        /** @var User $user */
        $user = $userRepository->findOneBy(['confirmationCode' => $code]);

        if ($user === null) {
            return new Response('404');
        }

        $user->setEnabled(true);
        $user->setConfirmationCode('');

        $em = $this->getDoctrine()->getManager();

        $em->flush();

        return $this->render('registration/account_confirm.html.twig', [
            'user' => $user,
        ]);
    }

}
