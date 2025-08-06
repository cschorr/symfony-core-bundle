<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Company;
use App\Entity\CompanyGroup;
use App\Entity\Project;
use App\Entity\SystemEntity;
use App\Entity\User;
use App\Entity\Contact;
use App\Entity\Category;
use App\Entity\UserGroup;
use App\Repository\SystemEntityRepository;
use Doctrine\ORM\EntityManagerInterface;

class NavigationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SystemEntityRepository $systemEntityRepository,
    ) {
    }

    /**
     * Get all active system entities that the user can access (has read or write permissions).
     *
     * @return SystemEntity[]
     */
    public function getAccessibleSystemEntitiesForUser(User $user): array
    {
        return $this->systemEntityRepository->findActiveSystemEntitiesForUser($user->getUserGroups()->toArray());
    }

    /**
     * Check if user can access a specific system entity (system entity is active and user has permissions).
     */
    public function canUserAccessSystemEntity(User $user, string $systemEntityCode): bool
    {
        $systemEntity = $this->systemEntityRepository->findOneBy(['code' => $systemEntityCode, 'active' => true]);

        if (null === $systemEntity) {
            return false;
        }

        return $this->systemEntityRepository->userHasSystemEntityPermission($user, $systemEntity);
    }

    /**
     * Get all active system entities (admin view).
     *
     * @return SystemEntity[]
     */
    public function getAllActiveSystemEntities(): array
    {
        return $this->systemEntityRepository->findBy(['active' => true], ['name' => 'ASC']);
    }

    /**
     * Check if user has admin role.
     */
    public function isUserAdmin(User $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles(), true);
    }

    /**
     * Get system entity by code for icon display.
     */
    public function getSystemEntityIcon(SystemEntity $systemEntity): string
    {
        return $systemEntity->getIcon() ?? 'fas fa-circle';
    }

    /**
     * Get system entity-to-entity class mapping dynamically from database.
     */
    public function getSystemEntityEntityMapping(): array
    {
        // Predefined mapping of system entity codes to their corresponding entity classes
        $classMapping = [
            'SystemEntity' => SystemEntity::class,
            'User' => User::class,
            'UserGroup' => UserGroup::class,
            'Company' => Company::class,
            'CompanyGroup' => CompanyGroup::class,
            'Contact' => Contact::class,
            'Project' => Project::class,
            'Category' => Category::class,
        ];

        // Get only active system entities from database
        $activeSystemEntities = $this->systemEntityRepository->findBy(['active' => true]);
        $activeCodes = array_map(fn ($entity) => $entity->getCode(), $activeSystemEntities);

        // Return only mappings for active system entities
        return array_filter($classMapping, fn ($key) => in_array($key, $activeCodes, true), ARRAY_FILTER_USE_KEY);
    }
}
