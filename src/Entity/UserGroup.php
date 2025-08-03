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

    /**
     * @var array<string>|null
     */
    #[ORM\Column(nullable: true)]
    private ?array $roles = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'userGroups')]
    private Collection $users;

    public function __construct()
    {
        parent::__construct();
        $this->users = new ArrayCollection();
    }

    /**
     * @return string[]|null
     */
    public function getRoles(): ?array
    {
        return $this->roles;
    }

    /**
     * @param array<string>|null $roles
     * @return $this
     */
    public function setRoles(?array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->addUserGroup($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            $user->removeUserGroup($this);
        }

        return $this;
    }
}
