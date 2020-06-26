<?php


namespace App\Service;

use App\Entity\User;
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
    private $key;


    public function __construct(RouterInterface $router, string $key)
    {
        $this->router = $router;
        $this->key = $key;
    }


    public function sendConfirmationMessage(User $user)
    {
        $mailer = $this->getSendGridMailer();

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
        $mailer = $this->getSendGridMailer();
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

        $mailer = $this->getSendGridMailer();
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
}