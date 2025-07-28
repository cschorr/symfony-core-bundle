<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Traits\Single\StringNameTrait;
use App\Repository\UserGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserGroupRepository::class)]
#[ApiResource]
class UserGroup extends AbstractEntity
{
    use StringNameTrait;

    #[ORM\Column(nullable: true)]
    private ?array $roles = null;

    /**
     * @var Collection<int, UserGroup>
     */
    #[ORM\ManyToMany(targetEntity: UserGroup::class, mappedBy: 'userGroups')]
    private Collection $users;

    public function __construct()
    {
        parent::__construct();
        $this->users = new ArrayCollection();
    }

    public function getRoles(): ?array
    {
        return $this->roles;
    }

    public function setRoles(?array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @return Collection<int, UserGroup>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(UserGroup $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->addUserGroup($this);
        }

        return $this;
    }

    public function removeUser(UserGroup $user): static
    {
        if ($this->users->removeElement($user)) {
            $user->removeUserGroup($this);
        }

        return $this;
    }
}
