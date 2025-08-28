<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserGroupDomainEntityPermissionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserGroupDomainEntityPermissionRepository::class)]
#[ORM\Table(name: 'usergroup_system_entity_permission')]
#[ORM\UniqueConstraint(name: 'UNIQ_USER_SYSTEM_ENTITY', fields: ['userGroup', 'domainEntityPermission'])]
class UserGroupDomainEntityPermission extends AbstractEntity
{
    #[ORM\ManyToOne(targetEntity: UserGroup::class, inversedBy: 'userGroupDomainEntityPermissions')]
    #[ORM\JoinColumn(name: 'usergroup_id', nullable: false)]
    private ?UserGroup $userGroup = null;

    #[ORM\ManyToOne(targetEntity: DomainEntityPermission::class, inversedBy: 'userGroupPermissions')]
    #[ORM\JoinColumn(name: 'system_entity_id', nullable: false)]
    private ?DomainEntityPermission $domainEntityPermission = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $canRead = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $canWrite = false;

    public function __construct()
    {
        parent::__construct(); // Call parent constructor to set timestamps
        $this->canRead = false;
        $this->canWrite = false;
    }

    #[\Override]
    public function __toString(): string
    {
        return sprintf(
            '%s - %s (R:%s, W:%s)',
            $this->userGroup?->getName() ?? 'Unknown UserGroup',
            $this->domainEntityPermission?->getName() ?? 'Unknown DomainEntityPermission',
            $this->canRead ? 'Y' : 'N',
            $this->canWrite ? 'Y' : 'N'
        );
    }

    public function getUserGroup(): ?UserGroup
    {
        return $this->userGroup;
    }

    public function setUserGroup(?UserGroup $userGroup): static
    {
        $this->userGroup = $userGroup;

        return $this;
    }

    public function getDomainEntityPermission(): ?DomainEntityPermission
    {
        return $this->domainEntityPermission;
    }

    public function setDomainEntityPermission(?DomainEntityPermission $domainEntityPermission): static
    {
        $this->domainEntityPermission = $domainEntityPermission;

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
