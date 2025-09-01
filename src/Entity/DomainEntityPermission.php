<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\Single\StringCodeTrait;
use App\Entity\Traits\Single\StringNameTrait;
use App\Entity\Traits\Single\StringTextTrait;
use App\Repository\DomainEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DomainEntityRepository::class)]
class DomainEntityPermission extends AbstractEntity
{
    use StringNameTrait;
    use StringTextTrait;
    use StringCodeTrait;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $icon = null;

    /**
     * @var Collection<int, UserGroupDomainEntityPermission>
     */
    #[ORM\OneToMany(targetEntity: UserGroupDomainEntityPermission::class, mappedBy: 'domainEntityPermission', cascade: ['persist', 'remove'])]
    private Collection $userGroupPermissions;

    public function __construct()
    {
        parent::__construct();
        $this->userGroupPermissions = new ArrayCollection();
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->getName() ?? 'Unnamed DomainEntityPermission';
    }

    /**
     * @return Collection<int, UserGroupDomainEntityPermission>
     */
    public function getUserGroupPermissions(): Collection
    {
        return $this->userGroupPermissions;
    }

    public function addUserPermission(UserGroupDomainEntityPermission $userGroupPermission): static
    {
        if (!$this->userGroupPermissions->contains($userGroupPermission)) {
            $this->userGroupPermissions->add($userGroupPermission);
            $userGroupPermission->setDomainEntityPermission($this);
        }

        return $this;
    }

    public function removeUserPermission(UserGroupDomainEntityPermission $userGroupPermission): static
    {
        if ($this->userGroupPermissions->removeElement($userGroupPermission)) {
            // set the owning side to null (unless already changed)
            if ($userGroupPermission->getDomainEntityPermission() === $this) {
                $userGroupPermission->setDomainEntityPermission(null);
            }
        }

        return $this;
    }

    /**
     * Get all users who have read access to this system entity.
     *
     * @return UserGroup[]
     */
    public function getUsersWithReadAccess(): array
    {
        $users = [];
        foreach ($this->userGroupPermissions as $permission) {
            if ($permission->canRead()) {
                $users[] = $permission->getUserGroup();
            }
        }

        return $users;
    }

    /**
     * Get all users who have write access to this system entity.
     *
     * @return UserGroup[]
     */
    public function getUsersWithWriteAccess(): array
    {
        $users = [];
        foreach ($this->userGroupPermissions as $permission) {
            if ($permission->canWrite()) {
                $users[] = $permission->getUserGroup();
            }
        }

        return $users;
    }

    /**
     * Check if a user has read access to this system entity.
     */
    public function userHasReadAccess(UserGroup $user): bool
    {
        foreach ($this->userGroupPermissions as $permission) {
            if ($permission->getUserGroup() === $user && $permission->canRead()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a user has write access to this system entity.
     */
    public function userHasWriteAccess(UserGroup $user): bool
    {
        foreach ($this->userGroupPermissions as $permission) {
            if ($permission->getUserGroup() === $user && $permission->canWrite()) {
                return true;
            }
        }

        return false;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }
}
