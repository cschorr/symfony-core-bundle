<?php

declare(strict_types=1);

namespace C3net\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use C3net\CoreBundle\ApiResource\AppInfoCollection;
use C3net\CoreBundle\Service\VersionService;

/**
 * @implements ProviderInterface<AppInfoCollection>
 */
class AppInfoProvider implements ProviderInterface
{
    public function __construct(
        private readonly VersionService $versionService,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $versionInfo = $this->versionService->getVersionInfo();

        return new AppInfoCollection(
            version: $versionInfo['version'],
            commitHash: $versionInfo['commitHash'],
            buildDate: $versionInfo['buildDate'],
            environment: $versionInfo['environment'],
            phpVersion: $versionInfo['phpVersion'],
            symfonyVersion: $versionInfo['symfonyVersion']
        );
    }
}
