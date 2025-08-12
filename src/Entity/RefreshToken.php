<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken as BaseRefreshToken;

#[Entity]
#[Table(name: "refresh_tokens")]
#[ApiResource(
    operations: [
        new Post(
        new Post(
            uriTemplate: '/refresh_tokens',
            openapi: new Operation(
                summary: 'Create a new refresh token',
                description: 'This endpoint allows clients to obtain a new refresh token for authentication purposes.',
                tags: ['Login Check']
            )
        )
    ]
)]
class RefreshToken extends BaseRefreshToken
{
}
