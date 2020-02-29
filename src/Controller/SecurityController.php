<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RecoverFormType;
use App\Form\RecoverPasswordFormType;
use App\Repository\UserRepository;
use App\Service\CodeGenerator;
use App\Service\MailerSender;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     * @param AuthenticationUtils $authenticationUtils
     * @return Response
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \Exception('This method can be blank - it will be intercepted by the logout key on your firewall');
    }

    /**
     * @Route("/recover", name="app_recover")
     * @param Request $request
     * @param UserRepository $userRepository
     * @param CodeGenerator $codeGenerator *
     * @param MailerSender $mailer
     * @return Response
     */

    public function recoverPassword(Request $request, UserRepository $userRepository, CodeGenerator $codeGenerator, MailerSender $mailer)
    {
        $form = $this->createForm(RecoverFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $email = $form->get('email')->getData();
            $user = $userRepository->findOneBy(['email' => $email]);

            if (!$user) {
                return $this->render('security/form_recover.html.twig', [
                    'recoverForm' => $form->createView(),
                    'error' => 'Вибачте, такого email не знайдено!'
                ]);
            }
            $user->setConfirmationCode($codeGenerator->getConfirmationCode());

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();

            $mailer->sendRecoveryMessage($user);
            return $this->render('security/password_recover.html.twig', [
                'user' => $user,
            ]);

        }
        return $this->render('security/form_recover.html.twig', [
            'recoverForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/confirm-pass/{code}", name="recover_password_confirmation")
     * @param UserRepository $userRepository
     * @param string $code
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param MailerSender $mailer
     * @return Response
     */
    public function confirmRecoverPassword(
        UserRepository $userRepository,
        string $code,
        Request $request,
        UserPasswordEncoderInterface $passwordEncoder,
        MailerSender $mailer
    )
    {
        /** @var User $user */
        $user = $userRepository->findOneBy(['confirmationCode' => $code]);

        if ($user === null) {
            throw $this->createNotFoundException('Вибачте! Це посилання вже було використане!');
        }

        $form = $this->createForm(RecoverPasswordFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
           $user->setConfirmationCode('');
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $user->setEnabled(true);

            $em = $this->getDoctrine()->getManager();
            $em->flush();

            $mailer->sendSuccessMessage($user);
            return $this->render('security/password_recover_confirm.html.twig', [
                'user' => $user,
            ]);
        }

        return $this->render('security/form_exchange_password.html.twig', [
            'exchangeForm' => $form->createView(),
        ]);
    }

}
