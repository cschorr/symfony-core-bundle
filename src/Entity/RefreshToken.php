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
            openapi: new Operation(
                tags: ['Login Check']
            )
        )
    ]
)]
class RefreshToken extends BaseRefreshToken
{
}
