<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\Single\StringCodeTrait;
use App\Entity\Traits\Single\StringNameTrait;
use App\Entity\Traits\Single\StringTextTrait;
use App\Repository\SystemEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SystemEntityRepository::class)]
class SystemEntity extends AbstractEntity
{
    use StringNameTrait;
    use StringTextTrait;
    use StringCodeTrait;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $icon = null;

    /**
     * @var Collection<int, UserSystemEntityPermission>
     */
    #[ORM\OneToMany(targetEntity: UserSystemEntityPermission::class, mappedBy: 'systemEntity', cascade: ['persist', 'remove'])]
    private Collection $userPermissions;

    public function __construct()
    {
        parent::__construct();
        $this->userPermissions = new ArrayCollection();
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->getName() ?? 'Unnamed SystemEntity';
    }

    /**
     * @return Collection<int, UserSystemEntityPermission>
     */
    public function getUserPermissions(): Collection
    {
        return $this->userPermissions;
    }

    public function addUserPermission(UserSystemEntityPermission $userPermission): static
    {
        if (!$this->userPermissions->contains($userPermission)) {
            $this->userPermissions->add($userPermission);
            $userPermission->setSystemEntity($this);
        }

        return $this;
    }

    public function removeUserPermission(UserSystemEntityPermission $userPermission): static
    {
        if ($this->userPermissions->removeElement($userPermission)) {
            // set the owning side to null (unless already changed)
            if ($userPermission->getSystemEntity() === $this) {
                $userPermission->setSystemEntity(null);
            }
        }

        return $this;
    }

    /**
     * Get all users who have read access to this system entity.
     *
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
     * Get all users who have write access to this system entity.
     *
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
     * Check if a user has read access to this system entity.
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
     * Check if a user has write access to this system entity.
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
