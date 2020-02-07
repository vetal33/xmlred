<?php


namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\Bridge\Google\Transport\GmailSmtpTransport;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\RouterInterface;


class MailerSender
{
    public const FROM_ADDRESS = 'xmlred.xyz@gmail.com';

    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var string
     */
    private $user;
    /**
     * @var string
     */
    private $password;


    public function __construct(RouterInterface $router, string $user, string $password)
    {
        $this->router = $router;

        $this->user = $user;
        $this->password = $password;
    }



    public function sendConfirmationMessage(User $user)
    {
        $mailer =  $this->getMailer();

        $email = (new Email())
            ->from($this::FROM_ADDRESS)
            ->to($user->getEmail())
            ->subject('Ласкаво просимо на XmlRed!')
            ->text('Sending emails is fun again!')
            ->html(' <h3> Вітаємо '.$user->getEmail().' </h3><p>Ви подали заявку на реєстрацію на сайті XmlRed</p>' .
                '<p><a href="'.$this->router->generate('register_email_confirmation',['code' => $user->getConfirmationCode()],0) .'">'.
                'Натисніть тут для активації вашого аккаунту</a> </p>');
        //$mailerGmail->send($email);
       $mailer->send($email);
    }

    public function sendRecoveryMessage(User $user)
    {
        //$transport = new GmailSmtpTransport('xmlred.xyz@gmail.com', 'KhnfDdfg%^ert#$');
        //$mailerGmail = new GmailMailer($transport);
        $mailer =  $this->getMailer();
        $email = (new Email())
            ->from($this::FROM_ADDRESS)
            ->to($user->getEmail())
            ->subject('Запит на зміну паролю XmlRed!')
            ->text('Sending emails is fun again!')
            ->html(' <h3> Вітаємо '.$user->getEmail().' </h3><p>Ви подали заявку на зміну паролю на сайті XmlRed</p>' .
                '<p><a href="'.$this->router->generate('recover_password_confirmation',['code' => $user->getConfirmationCode()],0) .'">'.
                'Натисніть тут для завершення процедури зміни паролю</a> </p>');
        //$mailerGmail->send($email);
        $mailer->send($email);
    }

    public function sendSuccessMessage(User $user)
    {
       /* $transport = new GmailSmtpTransport('xmlred.xyz@gmail.com', 'KhnfDdfg%^ert#$');
        $mailerGmail = new GmailMailer($transport);*/
        $mailer =  $this->getMailer();
        $email = (new Email())
            ->from($this::FROM_ADDRESS)
            ->to($user->getEmail())
            ->subject('Пороль успішно змінено XmlRed!')
            ->text('Sending emails is fun again!')
            ->html(' <h3> Вітаємо '.$user->getEmail().' </h3><p>Ви змінили пароль на сайті XmlRed</p>');
        //$mailerGmail->send($email);
        $mailer->send($email);
    }

    private function getMailer()
    {
        $transport = new GmailSmtpTransport($this->user, $this->password);
        return $mailer = new Mailer($transport);
    }
}