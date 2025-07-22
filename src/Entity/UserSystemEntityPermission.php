<?php

namespace App\Entity;

use App\Repository\UserSystemEntityPermissionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserSystemEntityPermissionRepository::class)]
#[ORM\Table(name: 'user_system_entity_permission')]
#[ORM\UniqueConstraint(name: 'UNIQ_USER_SYSTEM_ENTITY', fields: ['user', 'systemEntity'])]
class UserSystemEntityPermission extends AbstractEntity
{
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'systemEntityPermissions')]
    #[ORM\JoinColumn(name: 'user_id', nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: SystemEntity::class, inversedBy: 'userPermissions')]
    #[ORM\JoinColumn(name: 'system_entity_id', nullable: false)]
    private ?SystemEntity $systemEntity = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $canRead = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $canWrite = false;

    public function __construct()
    {
        parent::__construct(); // Call parent constructor to set timestamps
        $this->canRead = false;
        $this->canWrite = false;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s - %s (R:%s, W:%s)',
            $this->user?->getEmail() ?? 'Unknown User',
            $this->systemEntity?->getName() ?? 'Unknown SystemEntity',
            $this->canRead ? 'Y' : 'N',
            $this->canWrite ? 'Y' : 'N'
        );
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getSystemEntity(): ?SystemEntity
    {
        return $this->systemEntity;
    }

    public function setSystemEntity(?SystemEntity $systemEntity): static
    {
        $this->systemEntity = $systemEntity;
        return $this;
    }

    public function canRead(): bool
    {
        return $this->canRead;
    }

    public function setCanRead(bool $canRead): static
    {
        $this->canRead = $canRead;
        return $this;
    }

    public function canWrite(): bool
    {
        return $this->canWrite;
    }

    public function setCanWrite(bool $canWrite): static
    {
        $this->canWrite = $canWrite;
        return $this;
    }

    public function hasReadAccess(): bool
    {
        return $this->canRead;
    }

    public function hasWriteAccess(): bool
    {
        return $this->canWrite;
    }

    public function hasFullAccess(): bool
    {
        return $this->canRead && $this->canWrite;
    }

    public function hasAnyAccess(): bool
    {
        return $this->canRead || $this->canWrite;
    }
}
