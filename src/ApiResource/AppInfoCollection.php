<?php

declare(strict_types=1);

namespace C3net\CoreBundle\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use C3net\CoreBundle\State\AppInfoProvider;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    shortName: 'AppInfo',
    operations: [
        new Get(
            uriTemplate: '/info',
            provider: AppInfoProvider::class,
            extraProperties: ['openapi_context' => ['tags' => ['System']]]
        ),
    ],
    normalizationContext: ['groups' => ['app_info:read']],
    paginationEnabled: false,
    security: 'is_granted("IS_AUTHENTICATED")'
)]
class AppInfoCollection
{
    public function __construct(
        #[Groups(['app_info:read'])]
        public readonly string $version,
        #[Groups(['app_info:read'])]
        public readonly string $commitHash,
        #[Groups(['app_info:read'])]
        public readonly string $buildDate,
        #[Groups(['app_info:read'])]
        public readonly string $environment,
        #[Groups(['app_info:read'])]
        public readonly string $phpVersion,
        #[Groups(['app_info:read'])]
        public readonly string $symfonyVersion,
    ) {
    }
}
