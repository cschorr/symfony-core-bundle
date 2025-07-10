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
            ->findBy(['active' => true], ['id' => 'ASC']);
    }

    /**
     * Get entity class name for a module based on its code
     * Dynamically builds the entity class name from the module code
     */
    public function getModuleEntityClass(string $moduleCode): string
    {
        return '\\App\\Entity\\' . $moduleCode;
    }

    /**
     * Get entity class name for a module
     * Dynamically builds the entity class name from the module's code field
     */
    public function getEntityClassFromModule(Module $module): string
    {
        return $this->getModuleEntityClass($module->getCode());
    }

    /**
     * Get CRUD controller class name for a module based on its code
     * Dynamically builds the CRUD controller class name from the module code
     */
    public function getModuleCrudControllerClass(string $moduleCode): string
    {
        return '\\App\\Controller\\Admin\\' . $moduleCode . 'CrudController';
    }

    /**
     * Get CRUD controller class name for a module
     * Dynamically builds the CRUD controller class name from the module's code field
     */
    public function getCrudControllerFromModule(Module $module): string
    {
        return $this->getModuleCrudControllerClass($module->getCode());
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
