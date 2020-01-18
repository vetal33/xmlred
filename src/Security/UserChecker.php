<?php


namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{

    /**
     * Checks the user account before authentication.
     *
     * @param UserInterface $user
     */
    public function checkPreAuth(UserInterface $user)
    {
        if (!$user instanceof User) {
            return;
        }

        // user is not authorization, show a generic Account Not Found message.
        if (!$user->isEnabled()) {
            throw new CustomUserMessageAuthenticationException('Ви не підтвердили свою пошту');
        }
    }

    /**
     * Checks the user account after authentication.
     *
     * @param UserInterface $user
     */
    public function checkPostAuth(UserInterface $user)
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isEnabled()) {
            throw new CustomUserMessageAuthenticationException ('Користувач не активований');
        }
    }
}