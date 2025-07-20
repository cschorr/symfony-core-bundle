<?php

namespace App\Service;

use App\Entity\SystemEntity;
use App\Entity\User;
use App\Repository\SystemEntityRepository;
use Doctrine\ORM\EntityManagerInterface;

class NavigationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SystemEntityRepository $systemEntityRepository,
    ) {
    }

    /**
     * Get all active system entities that the user can access (has read or write permissions)
     * @return SystemEntity[]
     */
    public function getAccessibleSystemEntitiesForUser(User $user): array
    {
        return $this->systemEntityRepository->findActiveSystemEntitiesForUser($user);
    }

    /**
     * Check if user can access a specific system entity (system entity is active and user has permissions)
     */
    public function canUserAccessSystemEntity(User $user, string $systemEntityCode): bool
    {
        $systemEntity = $this->systemEntityRepository->findOneBy(['code' => $systemEntityCode, 'active' => true]);
        
        if (!$systemEntity) {
            return false;
        }

        return $this->systemEntityRepository->userHasSystemEntityPermission($user, $systemEntity);
    }

    /**
     * Get all active system entities (admin view)
     * @return SystemEntity[]
     */
    public function getAllActiveSystemEntities(): array
    {
        return $this->systemEntityRepository->findBy(['active' => true], ['name' => 'ASC']);
    }

    /**
     * Check if user has admin role
     */
    public function isUserAdmin(User $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles());
    }

    /**
     * Get system entity by code for icon display
     */
    public function getSystemEntityIcon(SystemEntity $systemEntity): string
    {
        return $systemEntity->getIcon() ?? $this->getSystemEntityIconMapping()[$systemEntity->getCode()] ?? 'fas fa-circle';
    }

    /**
     * Get system entity-to-entity class mapping
     */
    public function getSystemEntityEntityMapping(): array
    {
        return [
            'SystemEntity' => \App\Entity\SystemEntity::class,
            'User' => \App\Entity\User::class,
            'Company' => \App\Entity\Company::class,
            'CompanyGroup' => \App\Entity\CompanyGroup::class,
            'Project' => \App\Entity\Project::class,
        ];
    }

    /**
     * Get system entity icon mapping for fallback
     */
    public function getSystemEntityIconMapping(): array
    {
        return [
            'SystemEntity' => 'fas fa-list',
            'User' => 'fas fa-users',
            'Company' => 'fas fa-building',
            'CompanyGroup' => 'fas fa-layer-group',
            'Project' => 'fas fa-project-diagram',
        ];
    }
}
