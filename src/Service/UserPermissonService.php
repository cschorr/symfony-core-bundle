<?php

namespace App\Service;

use App\Entity\SystemEntity;
use App\Entity\User;
use App\Repository\UserGroupSystemEntityPermissionRepository;

class UserPermissonService
{
    public function __construct(
        private readonly UserGroupSystemEntityPermissionRepository $userGroupSystemEntityPermissionRepository,
    ) {
    }

    /**
     * Check if user has read access to a system entity.
     */
    public function userHasReadAccess(User $user, SystemEntity $systemEntity): bool
    {
        foreach ($user->getUserGroups() as $userGroup) {
            $permission[] = $this->userGroupSystemEntityPermissionRepository->findByUserGroupAndSystemEntity($userGroup, $systemEntity);
        }

        return $permission && $permission->canRead();
    }

    /**
     * Check if user has write access to a system entity.
     */
    public function userHasWriteAccess(User $user, SystemEntity $systemEntity): bool
    {
        $permission = $this->userGroupSystemEntityPermissionRepository->findByUserGroupAndSystemEntity($user, $systemEntity);

        return $permission && $permission->canWrite();
    }
}
