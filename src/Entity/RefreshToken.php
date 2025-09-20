<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use Doctrine\ORM\Mapping as ORM;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken as BaseRefreshToken;

#[ORM\Entity]
#[ORM\Table(name: 'refresh_tokens')]
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/refresh_tokens',
            openapi: new Operation(
                tags: ['Login Check'],
                summary: 'Create a new refresh token',
                description: 'This endpoint allows clients to obtain a new refresh token for authentication purposes.'
            )
        ),
    ]
)]
class RefreshToken extends BaseRefreshToken
{
    // ID property is inherited from Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken
    // which is properly mapped via XML configuration in doctrine.yaml
}
