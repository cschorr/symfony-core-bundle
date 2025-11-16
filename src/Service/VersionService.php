<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Service;

use Symfony\Component\HttpKernel\Kernel;

/**
 * Service for managing application version information.
 *
 * Extracts version data from environment variables, git, and system info.
 */
class VersionService
{
    /**
     * @var array{version: string, commitHash: string, buildDate: string, environment: string, phpVersion: string, symfonyVersion: string}|null
     */
    private ?array $versionData = null;

    public function __construct(
        private readonly string $projectDir,
        private readonly string $environment,
    ) {
    }

    /**
     * Get all version information.
     *
     * @return array{version: string, commitHash: string, buildDate: string, environment: string, phpVersion: string, symfonyVersion: string}
     */
    public function getVersionInfo(): array
    {
        if (null !== $this->versionData) {
            return $this->versionData;
        }

        $this->versionData = [
            'version' => $this->getVersion(),
            'commitHash' => $this->getCommitHash(),
            'buildDate' => $this->getBuildDate(),
            'environment' => $this->environment,
            'phpVersion' => PHP_VERSION,
            'symfonyVersion' => Kernel::VERSION,
        ];

        return $this->versionData;
    }

    /**
     * Get application version.
     *
     * Priority order:
     * 1. VERSION environment variable (Docker build)
     * 2. Git describe output
     * 3. Fallback to composer.json version or 'dev'
     */
    private function getVersion(): string
    {
        // Priority 1: Environment variable (set during Docker build)
        $envVersion = $_ENV['APP_VERSION'] ?? null;
        if (null !== $envVersion && '' !== $envVersion) {
            return $envVersion;
        }

        // Priority 2: Git describe
        $gitVersion = $this->executeCommand('git describe --tags --always --dirty 2>/dev/null');
        if (null !== $gitVersion) {
            return $gitVersion;
        }

        // Priority 3: Composer.json version
        $composerFile = $this->projectDir . '/composer.json';
        if (file_exists($composerFile)) {
            $composerContent = file_get_contents($composerFile);
            if (false !== $composerContent) {
                $composerData = json_decode($composerContent, true);
                if (isset($composerData['version'])) {
                    return $composerData['version'];
                }
            }
        }

        // Fallback
        return 'dev';
    }

    /**
     * Get git commit hash.
     */
    private function getCommitHash(): string
    {
        // Priority 1: Environment variable (set during Docker build)
        $envHash = $_ENV['COMMIT_HASH'] ?? null;
        if (null !== $envHash && '' !== $envHash) {
            return $envHash;
        }

        // Priority 2: Git command
        $gitHash = $this->executeCommand('git rev-parse --short HEAD 2>/dev/null');
        if (null !== $gitHash) {
            return $gitHash;
        }

        return 'unknown';
    }

    /**
     * Get build date.
     */
    private function getBuildDate(): string
    {
        // Priority 1: Environment variable (set during Docker build)
        $envDate = $_ENV['BUILD_DATE'] ?? null;
        if (null !== $envDate && '' !== $envDate) {
            return $envDate;
        }

        // Priority 2: Current date/time
        return (new \DateTimeImmutable())->format(\DateTimeImmutable::ATOM);
    }

    /**
     * Execute a shell command and return output.
     */
    private function executeCommand(string $command): ?string
    {
        try {
            $output = shell_exec($command);
            if (null === $output || false === $output) {
                return null;
            }

            $result = trim($output);

            return '' !== $result ? $result : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
