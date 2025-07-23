<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Service for managing application locales.
 *
 * This service centralizes locale management and ensures synchronization
 * between services.yaml configuration and route requirements.
 */
class LocaleService
{
    public function __construct(
        #[Autowire(param: 'app.locales')]
        private readonly array $appLocales,
    ) {
    }

    /**
     * Get all supported locales.
     */
    public function getSupportedLocales(): array
    {
        return $this->appLocales;
    }

    /**
     * Generate route pattern for locale requirements.
     */
    public function getLocaleRoutePattern(): string
    {
        return implode('|', $this->appLocales);
    }

    /**
     * Check if a locale is supported.
     */
    public function isLocaleSupported(string $locale): bool
    {
        return in_array($locale, $this->appLocales, true);
    }

    /**
     * Get display name and flag for locale.
     */
    public function getLocaleDisplayName(string $locale): string
    {
        return match ($locale) {
            'en' => '🇺🇸 English',
            'de' => '🇩🇪 Deutsch',
            default => strtoupper($locale),
        };
    }

    /**
     * Generate EasyAdmin locale mapping.
     */
    public function getEasyAdminLocales(): array
    {
        $localeMap = [];
        foreach ($this->appLocales as $locale) {
            $localeMap[$locale] = $this->getLocaleDisplayName($locale);
        }

        return $localeMap;
    }
}
