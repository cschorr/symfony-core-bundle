<?php

namespace App\Service;

use App\Entity\Module;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service for handling module-based translations
 * 
 * This service uses the Module entity data to provide automatic translations:
 * - code field: singular form (e.g., "User", "Company")
 * - name field: plural form (e.g., "Users", "Companies")
 */
class ModuleTranslationService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Get all module translation mappings
     * 
     * @return array Array with both singular and plural translations
     */
    public function getModuleTranslationMappings(): array
    {
        $modules = $this->entityManager->getRepository(Module::class)->findAll();
        $translations = [];

        foreach ($modules as $module) {
            $code = $module->getCode(); // Singular (e.g., "User")
            $name = $module->getName(); // Plural (e.g., "Users")
            
            // Add both singular and plural mappings
            $translations[$code] = $this->getGermanTranslation($code, 'singular');
            $translations[$name] = $this->getGermanTranslation($code, 'plural');
        }

        return $translations;
    }

    /**
     * Get German translation for a module code
     * 
     * @param string $code The module code (e.g., "User", "Company")
     * @param string $form Either 'singular' or 'plural'
     * @return string The German translation
     */
    private function getGermanTranslation(string $code, string $form): string
    {
        $translations = [
            'User' => ['singular' => 'Benutzer', 'plural' => 'Benutzer'],
            'Company' => ['singular' => 'Unternehmen', 'plural' => 'Unternehmen'],
            'Module' => ['singular' => 'Modul', 'plural' => 'Module'],
            'CompanyGroup' => ['singular' => 'Unternehmensgruppe', 'plural' => 'Unternehmensgruppen'],
            'Project' => ['singular' => 'Projekt', 'plural' => 'Projekte'],
        ];

        return $translations[$code][$form] ?? $code;
    }

    /**
     * Get English translation for a module code
     * 
     * @param string $code The module code (e.g., "User", "Company")
     * @param string $form Either 'singular' or 'plural'
     * @return string The English translation
     */
    private function getEnglishTranslation(string $code, string $form): string
    {
        $translations = [
            'User' => ['singular' => 'User', 'plural' => 'Users'],
            'Company' => ['singular' => 'Company', 'plural' => 'Companies'],
            'Module' => ['singular' => 'Module', 'plural' => 'Modules'],
            'CompanyGroup' => ['singular' => 'Company Group', 'plural' => 'Company Groups'],
            'Project' => ['singular' => 'Project', 'plural' => 'Projects'],
        ];

        return $translations[$code][$form] ?? $code;
    }

    /**
     * Generate translation array for a specific locale
     * 
     * @param string $locale The locale (e.g., 'de', 'en')
     * @return array Translation array
     */
    public function generateTranslationsForLocale(string $locale): array
    {
        $modules = $this->entityManager->getRepository(Module::class)->findAll();
        $translations = [];

        foreach ($modules as $module) {
            $code = $module->getCode(); // Singular
            $name = $module->getName(); // Plural
            
            if ($locale === 'de') {
                $translations[$code] = $this->getGermanTranslation($code, 'singular');
                $translations[$name] = $this->getGermanTranslation($code, 'plural');
            } elseif ($locale === 'en') {
                $translations[$code] = $this->getEnglishTranslation($code, 'singular');
                $translations[$name] = $this->getEnglishTranslation($code, 'plural');
            }
        }

        return $translations;
    }

    /**
     * Get the navigation label for a module code in the current locale
     * 
     * @param string $moduleCode The module code
     * @param string $locale The locale
     * @return string The translated label
     */
    public function getNavigationLabel(string $moduleCode, string $locale): string
    {
        // For navigation, we typically want the plural form
        if ($locale === 'de') {
            return $this->getGermanTranslation($moduleCode, 'plural');
        } elseif ($locale === 'en') {
            return $this->getEnglishTranslation($moduleCode, 'plural');
        }

        // Fallback: use English if locale is not supported
        return $this->getEnglishTranslation($moduleCode, 'plural');
    }
}
