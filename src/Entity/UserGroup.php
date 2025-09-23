<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use C3net\CoreBundle\Entity\Traits\Single\StringNameTrait;
use C3net\CoreBundle\Enum\UserRole;
use C3net\CoreBundle\Repository\UserGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserGroupRepository::class)]
#[ApiResource(
    operations: [
        new Get(uriTemplate: '/user-groups/{id}'),
        new GetCollection(uriTemplate: '/user-groups'),
        new Post(uriTemplate: '/user-groups', processor: 'C3net\CoreBundle\State\UserGroupWriteProcessor'),
        new Put(uriTemplate: '/user-groups/{id}', processor: 'C3net\CoreBundle\State\UserGroupWriteProcessor'),
        new Delete(uriTemplate: '/user-groups/{id}'),
    ]
)]
class UserGroup extends AbstractEntity
{
    use StringNameTrait;

    /**
     * Stored as list of strings in DB; use helper methods to work with enums.
     *
     * @var list<string>|null
     */
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::JSON, nullable: true)]
    #[Assert\Choice(callback: [UserRole::class, 'values'], multiple: true)]
    #[Assert\Unique]
    private ?array $roles = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Category $category = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'userGroups')]
    #[ORM\JoinTable(name: 'user_user_group')]
    private Collection $users;

    public function __construct()
    {
        parent::__construct();
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
        if (null === $roles) {
            $this->roles = null;

            return $this;
        }

        $this->roles = array_values(array_unique(array_map(
            static fn (string|UserRole $r) => $r instanceof UserRole ? $r->value : (string) $r,
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

        // No need for array_values since $stored is already a list
        return array_map(static fn (string $r) => UserRole::from($r), $stored);
    }

    /**
     * Replace roles using enums.
     *
     * @param list<UserRole> $roles
     */
    public function setRolesFromEnums(array $roles): static
    {
        $this->roles = array_values(array_unique(array_map(static fn (UserRole $r) => $r->value, $roles)));

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
            // Since UserGroup is now the owning side, we need to update the inverse side manually
            if (!$user->getUserGroups()->contains($this)) {
                $user->getUserGroups()->add($this);
            }
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            // Update the inverse side
            $user->getUserGroups()->removeElement($this);
        }

        return $this;
    }
}
