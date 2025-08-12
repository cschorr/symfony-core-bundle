<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Traits\Single\StringNameTrait;
use App\Enum\UserRole;
use App\Repository\UserGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserGroupRepository::class)]
#[ApiResource]
class UserGroup extends AbstractEntity
{
    use StringNameTrait;

    /**
     * Stored as list of strings in DB; use helper methods to work with enums.
     *
     * @var list<string>|null
     */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Assert\Choice(callback: [UserRole::class, 'values'], multiple: true)]
    #[Assert\Unique]
    private ?array $roles = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Category $category = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'userGroups')]
    private Collection $users;

    /**
     * @var Collection<int, UserGroupDomainEntityPermission>
     */
    #[ORM\OneToMany(targetEntity: UserGroupDomainEntityPermission::class, mappedBy: 'userGroup', cascade: ['persist', 'remove'])]
    #[Groups(['user:read'])]
    private Collection $userGroupDomainEntityPermissions;

    public function __construct()
    {
        parent::__construct();
        $this->userGroupDomainEntityPermissions = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    /**
     * String roles (for backward compatibility).
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        return array_values(array_unique($this->roles ?? []));
    }

    /**
     * Accept strings or enums and store as strings.
     *
     * @param list<string|UserRole>|null $roles
     */
    public function setRoles(?array $roles): static
    {
        if ($roles === null) {
            $this->roles = null;
            return $this;
        }

        $this->roles = array_values(array_unique(array_map(
            static fn(string|UserRole $r) => $r instanceof UserRole ? $r->value : (string) $r,
            $roles
        )));

        return $this;
    }

    /**
     * Enum roles access.
     *
     * @return list<UserRole>
     */
    #[Ignore]
    public function getRoleEnums(): array
    {
        $stored = $this->roles ?? [];
        return array_values(
            array_map(static fn(string $r) => UserRole::from($r), $stored)
        );
    }

    /**
     * Replace roles using enums.
     *
     * @param list<UserRole> $roles
     */
    public function setRolesFromEnums(array $roles): static
    {
        $this->roles = array_values(array_unique(array_map(static fn(UserRole $r) => $r->value, $roles)));

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

    /**
     * @return array<int, User>
     */
    public function getUsersArray(): array
    {
        return $this->users->toArray();
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
