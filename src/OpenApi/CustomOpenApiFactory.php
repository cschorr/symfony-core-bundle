<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Server;
use ApiPlatform\OpenApi\OpenApi;

final class CustomOpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(private OpenApiFactoryInterface $decorated)
    {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        // keep any existing servers and add yours
        $servers = $openApi->getServers() ?? [];
        $servers[] = new Server('https://api.example.com', 'Production API');
        $stagingUrl = getenv('STAGING_API_URL');
        if ($stagingUrl) {
            $servers[] = new Server($stagingUrl, 'Staging API');
        }

        return $openApi->withServers($servers);
    }
}
