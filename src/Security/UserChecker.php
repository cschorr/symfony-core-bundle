<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Security;

use C3net\CoreBundle\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * UserChecker is called during authentication to verify user account status.
 * It ensures locked or inactive users cannot authenticate.
 */
class UserChecker implements UserCheckerInterface
{
    /**
     * Checks the user account before authentication.
     *
     * @throws CustomUserMessageAccountStatusException if the user account is locked or inactive
     */
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->isLocked()) {
            throw new CustomUserMessageAccountStatusException('Your account has been locked. Please contact an administrator.');
        }

        if (!$user->isActive()) {
            throw new CustomUserMessageAccountStatusException('Your account is inactive. Please contact an administrator.');
        }
    }

    /**
     * Checks the user account after authentication.
     * Currently no post-authentication checks are needed.
     */
    public function checkPostAuth(UserInterface $user): void
    {
        // No additional checks needed after authentication
    }
}
