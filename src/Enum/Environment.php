<?php

namespace App\Enum;

enum Environment: string
{
    case DEV = 'dev';
    case STAGE = 'stage';
    case PROD = 'prod';

    /**
     * Returns a human-readable label for the environment
     */
    public function getLabel(): string
    {
        return match($this) {
            self::DEV => 'Development',
            self::STAGE => 'Staging',
            self::PROD => 'Production',
        };
    }

    /**
     * Get Environment from string value
     *
     * @param string $value
     * @return self|null Returns null if no matching environment is found
     */
    public static function fromString(string $value): ?self
    {
        $normalized = strtolower(trim($value));

        return match($normalized) {
            'dev', 'development' => self::DEV,
            'stage', 'staging' => self::STAGE,
            'prod', 'production' => self::PROD,
            default => null,
        };
    }

    /**
     * Try to determine the current environment
     */
    public static function getCurrent(): self
    {
        $appEnv = $_ENV['APP_ENV'] ?? null;

        if ($appEnv) {
            $env = self::fromString($appEnv);
            if ($env) {
                return $env;
            }
        }

        // Default to dev if we can't determine the environment
        return self::DEV;
    }
}
