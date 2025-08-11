<?php

namespace App\Entity;

use ApiPlatform\Metadata as API;
use App\Entity\Traits\Single\BoolActiveTrait;
use App\Entity\Traits\Single\UuidTrait;
use App\Enum\DomainEntityType;
use App\Repository\ThreadRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ThreadRepository::class)]
#[API\ApiResource(
    mercure: true,
    normalizationContext: ['groups' => ['thread:read']],
    denormalizationContext: ['groups' => ['thread:write']],
    operations: [
        new API\Get(security: "is_granted('ROLE_USER')"),
        new API\GetCollection(security: "is_granted('ROLE_USER')"),
        new API\Post(security: "is_granted('ROLE_USER')"),
        new API\Patch(security: "is_granted('ROLE_USER')"),
        new API\Delete(security: "is_granted('ROLE_ADMIN')"),
    ]
)]
class Thread
{
    use UuidTrait;
    use BoolActiveTrait;

    #[ORM\Column(type: Types::STRING, enumType: DomainEntityType::class)]
    #[Assert\NotBlank]
    #[Groups(['thread:read','thread:write','comment:read'])]
    private DomainEntityType $resourceType;

    #[ORM\Column(length: 128)]
    #[Groups(['thread:read','thread:write','comment:read'])]
    private string $resourceId;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['thread:read','thread:write'])]
    private ?string $title = null;

    public function getResourceType(): DomainEntityType { return $this->resourceType; }
    public function setResourceType(DomainEntityType $v): self { $this->resourceType = $v; return $this; }
    public function getResourceId(): string { return $this->resourceId; }
    public function setResourceId(string $v): self { $this->resourceId = $v; return $this; }
    public function getTitle(): ?string { return $this->title; }
    public function setTitle(?string $v): self { $this->title = $v; return $this; }
}
