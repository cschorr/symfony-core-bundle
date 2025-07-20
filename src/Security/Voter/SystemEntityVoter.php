<?php

namespace App\Security\Voter;

use App\Entity\SystemEntity;
use App\Entity\User;
use App\Repository\UserSystemEntityPermissionRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\User\UserInterface;

class SystemEntityVoter extends Voter
{
    public const READ = 'read';
    public const WRITE = 'write';
    public const EDIT = 'edit';
    public const DELETE = 'delete';

    private UserSystemEntityPermissionRepository $permissionRepository;

    public function __construct(UserSystemEntityPermissionRepository $permissionRepository)
    {
        $this->permissionRepository = $permissionRepository;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Support both SystemEntity entities and system entity codes/names (strings)
        return in_array($attribute, [self::READ, self::WRITE, self::EDIT, self::DELETE])
            && ($subject instanceof SystemEntity || is_string($subject));
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        
        // User must be logged in
        if (!$user instanceof UserInterface) {
            return false;
        }

        // Admin users have all permissions
        if ($user instanceof User && in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Get the system entity
        $systemEntity = $subject;
        if (is_string($subject)) {
            // Try to resolve string to SystemEntity entity
            $systemEntity = $this->resolveSystemEntityFromString($subject);
            if (!$systemEntity) {
                return false;
            }
        }

        if (!$systemEntity instanceof SystemEntity) {
            return false;
        }

        // Check specific permission
        switch ($attribute) {
            case self::READ:
                return $this->canRead($systemEntity, $user);
            case self::WRITE:
            case self::EDIT:
            case self::DELETE:
                return $this->canWrite($systemEntity, $user);
        }

        return false;
    }

    private function canRead(SystemEntity $systemEntity, User $user): bool
    {
        return $this->permissionRepository->userHasReadAccess($user, $systemEntity);
    }

    private function canWrite(SystemEntity $systemEntity, User $user): bool
    {
        return $this->permissionRepository->userHasWriteAccess($user, $systemEntity);
    }

    /**
     * Try to resolve a string (system entity code or name) to a SystemEntity entity
     */
    private function resolveSystemEntityFromString(string $subject): ?SystemEntity
    {
        // This could be a system entity code like 'User', 'Company', etc.
        // For now, we'll return null and let it fail - proper implementation would need a service
        // that can resolve system entity names/codes to entities
        return null;
    }
}
