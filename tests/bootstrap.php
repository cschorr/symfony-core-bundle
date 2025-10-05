<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

// Bundle is a submodule, use parent project's autoloader
if (file_exists(dirname(__DIR__).'/vendor/autoload.php')) {
    // Standalone bundle with own vendor
    require dirname(__DIR__).'/vendor/autoload.php';
} elseif (file_exists(dirname(__DIR__, 3).'/vendor/autoload.php')) {
    // Bundle as submodule in parent project
    require dirname(__DIR__, 3).'/vendor/autoload.php';
} else {
    throw new \RuntimeException('Could not find vendor/autoload.php');
}

// Load .env file if it exists (bundle may not have one as submodule)
if (method_exists(Dotenv::class, 'bootEnv')) {
    $envFile = dirname(__DIR__).'/.env';
    if (!file_exists($envFile)) {
        // Try parent project's .env
        $envFile = dirname(__DIR__, 3).'/.env';
    }
    if (file_exists($envFile)) {
        (new Dotenv())->bootEnv($envFile);
    }
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
