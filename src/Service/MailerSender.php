<?php


namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\Bridge\Google\Transport\GmailSmtpTransport;
use Symfony\Component\Mailer\Bridge\Sendgrid\Transport\SendgridSmtpTransport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\RouterInterface;


class MailerSender
{
    public const FROM_ADDRESS = 'landprice.online@gmail.com';

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
        $mailer = $this->getGmailMailer();

        $email = (new Email())
            ->from($this::FROM_ADDRESS)
            ->to($user->getEmail())
            ->subject('Ласкаво просимо на LANDPrice!')
            ->text('Sending emails is fun again!')
            ->html(' <h3> Вітаємо ' . $user->getEmail() . ' </h3><p>Ви подали заявку на реєстрацію на сайті LANDPrice.online</p>' .
                '<p><a href="' . $this->router->generate('register_email_confirmation', ['code' => $user->getConfirmationCode()], 0) . '">' .
                'Перейдіть по цьому посиланню для активації вашого аккаунту</a> </p>');
        $mailer->send($email);
    }

    public function sendRecoveryMessage(User $user)
    {
        $mailer = $this->getGmailMailer();
        $email = (new Email())
            ->from($this::FROM_ADDRESS)
            ->to($user->getEmail())
            ->subject('Запит на зміну паролю LANDPrice!')
            ->text('Sending emails is fun again!')
            ->html(' <h3> Вітаємо ' . $user->getEmail() . ' </h3><p>Ви подали заявку на зміну паролю на сайті LANDPrice.online</p>' .
                '<p><a href="' . $this->router->generate('recover_password_confirmation', ['code' => $user->getConfirmationCode()], 0) . '">' .
                'Перейдіть по цьому посиланню для завершення процедури зміни паролю</a> </p>');
        $mailer->send($email);
    }

    public function sendSuccessMessage(User $user)
    {

        $mailer = $this->getGmailMailer();
        $email = (new Email())
            ->from($this::FROM_ADDRESS)
            ->to($user->getEmail())
            ->subject('Пороль успішно змінено LANDPrice.online!')
            ->text('Sending emails is fun again!')
            ->html(' <h3> Вітаємо ' . $user->getEmail() . ' </h3><p>Ви змінили пароль на сайті LANDPrice.online</p>');
        $mailer->send($email);
    }

    private function getSendGridMailer()
    {
        $transport = new SendgridSmtpTransport($this->key);

        return $mailer = new Mailer($transport);
    }

    private function getGmailMailer()
    {
        $transport = new GmailSmtpTransport($this->user, $this->password);
        return $mailer = new Mailer($transport);
    }
}