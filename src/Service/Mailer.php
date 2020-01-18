<?php


namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;


class Mailer
{
    public const FROM_ADDRESS = 'xmlred.xyz@gmail.com';

    /**
     * @var  MailerInterface
     */
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendConfirmationMessage(User $user)
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this::FROM_ADDRESS, 'XmlRed'))
            ->to(new Address($user->getEmail()))
            ->subject('Ласкаво просимо на XmlRed!')
            ->htmlTemplate('email/simple.html.twig')
            ->context([
                'user' => $user,
            ]);
        $this->mailer->send($email);
    }


}