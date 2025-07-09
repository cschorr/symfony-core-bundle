<?php

namespace App\Service;

use App\Entity\Module;
use App\Entity\User;
use App\Repository\ModuleRepository;
use Doctrine\ORM\EntityManagerInterface;

class NavigationService
{
    public function __construct(
        private ModuleRepository $moduleRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Get all active modules that the user can access (has read or write permissions)
     * @return Module[]
     */
    public function getAccessibleModulesForUser(User $user): array
    {
        return $this->moduleRepository->findActiveModulesForUser($user);
    }

    /**
     * Check if user can access a specific module (module is active and user has permissions)
     */
    public function canUserAccessModule(User $user, string $moduleCode): bool
    {
        $module = $this->entityManager->getRepository(Module::class)
            ->findOneBy(['code' => $moduleCode, 'active' => true]);

        if (!$module) {
            return false;
        }

        // Check if user has any permission for this module
        foreach ($user->getModulePermissions() as $permission) {
            if ($permission->getModule() === $module && 
                ($permission->canRead() || $permission->canWrite())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user is admin (has ROLE_ADMIN)
     */
    public function isUserAdmin(User $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles());
    }

    /**
     * Get all active modules for admin users
     * @return Module[]
     */
    public function getAllActiveModules(): array
    {
        return $this->entityManager->getRepository(Module::class)
            ->findBy(['active' => true], ['name' => 'ASC']);
    }

    /**
     * Get module entity mapping for navigation
     * This maps module codes to their corresponding entity classes
     */
    public function getModuleEntityMapping(): array
    {
        return [
            'Module' => \App\Entity\Module::class,
            'User' => \App\Entity\User::class,
            'Company' => \App\Entity\Company::class,
            'CompanyGroup' => \App\Entity\CompanyGroup::class,
            // Add more mappings as needed
        ];
    }

    /**
     * Get module icon mapping for navigation
     * This maps module codes to their FontAwesome icons
     * @deprecated Use Module::getIcon() instead - icons are now stored in database
     */
    public function getModuleIconMapping(): array
    {
        // This method is deprecated but kept for backward compatibility
        // Icons are now stored in the database on the Module entity
        return [
            'Module' => 'fas fa-list',
            'User' => 'fas fa-users',
            'Company' => 'fas fa-building',
            'CompanyGroup' => 'fas fa-users',
            // Add more icon mappings as needed
        ];
    }

    /**
     * Get icon for a module from the database
     */
    public function getModuleIcon(Module $module): string
    {
        // Return the icon from the database, or a default icon if not set
        return $module->getIcon() ?? 'fas fa-list';
    }
}
