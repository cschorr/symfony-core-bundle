<?php

namespace App\Security\Voter;

use App\Entity\Module;
use App\Entity\User;
use App\Repository\UserModulePermissionRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\User\UserInterface;

class ModuleVoter extends Voter
{
    public const READ = 'read';
    public const WRITE = 'write';
    public const EDIT = 'edit';
    public const DELETE = 'delete';

    private UserModulePermissionRepository $permissionRepository;

    public function __construct(UserModulePermissionRepository $permissionRepository)
    {
        $this->permissionRepository = $permissionRepository;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Support both Module entities and module codes/names (strings)
        return in_array($attribute, [self::READ, self::WRITE, self::EDIT, self::DELETE])
            && ($subject instanceof Module || is_string($subject));
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

        // Get the module
        $module = $subject;
        if (is_string($subject)) {
            // First try to find by code, then by name for backward compatibility
            $module = $this->permissionRepository->getEntityManager()
                ->getRepository(Module::class)
                ->findOneBy(['code' => $subject]);
            
            if (!$module) {
                $module = $this->permissionRepository->getEntityManager()
                    ->getRepository(Module::class)
                    ->findOneBy(['name' => $subject]);
            }
        }

        if (!$module instanceof Module) {
            return false;
        }

        // Check user permissions
        return $this->hasPermission($user, $module, $attribute);
    }

    private function hasPermission(UserInterface $user, Module $module, string $attribute): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        switch ($attribute) {
            case self::READ:
                return $this->permissionRepository->hasReadAccess($user, $module);
            
            case self::WRITE:
            case self::EDIT:
            case self::DELETE:
                return $this->permissionRepository->hasWriteAccess($user, $module);
        }

        return false;
    }
}
