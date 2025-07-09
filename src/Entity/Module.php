<?php

namespace App\Entity;

use App\Entity\Traits\Single\StringNameTrait;
use App\Entity\Traits\Single\StringTextTrait;
use App\Entity\Traits\Single\StringCodeTrait;
use App\Repository\ModuleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ModuleRepository::class)]
class Module extends AbstractEntity
{
    use StringNameTrait;
    use StringTextTrait;
    use StringCodeTrait;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $icon = null;

    /**
     * @var Collection<int, UserModulePermission>
     */
    #[ORM\OneToMany(targetEntity: UserModulePermission::class, mappedBy: 'module', cascade: ['persist', 'remove'])]
    private Collection $userPermissions;

    public function __construct()
    {
        parent::__construct();
        $this->userPermissions = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getName() ?? 'Unnamed Module';
    }

    /**
     * @return Collection<int, UserModulePermission>
     */
    public function getUserPermissions(): Collection
    {
        return $this->userPermissions;
    }

    public function addUserPermission(UserModulePermission $userPermission): static
    {
        if (!$this->userPermissions->contains($userPermission)) {
            $this->userPermissions->add($userPermission);
            $userPermission->setModule($this);
        }

        return $this;
    }

    public function removeUserPermission(UserModulePermission $userPermission): static
    {
        if ($this->userPermissions->removeElement($userPermission)) {
            // set the owning side to null (unless already changed)
            if ($userPermission->getModule() === $this) {
                $userPermission->setModule(null);
            }
        }

        return $this;
    }

    /**
     * Get all users who have read access to this module
     * @return User[]
     */
    public function getUsersWithReadAccess(): array
    {
        $users = [];
        foreach ($this->userPermissions as $permission) {
            if ($permission->canRead()) {
                $users[] = $permission->getUser();
            }
        }
        return $users;
    }

    /**
     * Get all users who have write access to this module
     * @return User[]
     */
    public function getUsersWithWriteAccess(): array
    {
        $users = [];
        foreach ($this->userPermissions as $permission) {
            if ($permission->canWrite()) {
                $users[] = $permission->getUser();
            }
        }
        return $users;
    }

    /**
     * Check if a user has read access to this module
     */
    public function userHasReadAccess(User $user): bool
    {
        foreach ($this->userPermissions as $permission) {
            if ($permission->getUser() === $user && $permission->canRead()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if a user has write access to this module
     */
    public function userHasWriteAccess(User $user): bool
    {
        foreach ($this->userPermissions as $permission) {
            if ($permission->getUser() === $user && $permission->canWrite()) {
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
