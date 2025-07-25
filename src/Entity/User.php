<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Traits\Set\SetCommunicationTrait;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ApiResource(
    shortName: 'User',
    description: 'Represents a user in the system, with roles and permissions.',
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']]
)]
class User extends AbstractEntity implements UserInterface, PasswordAuthenticatedUserInterface
{
    use SetCommunicationTrait;

    #[ORM\Column(length: 180)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $email = null;

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * @var Collection<int, Project>
     */
    #[ORM\OneToMany(targetEntity: Project::class, mappedBy: 'assignee')]
    #[Groups(['user:read'])]
    private Collection $projects;

    #[ORM\ManyToOne(inversedBy: 'employees')]
    #[Groups(['user:read', 'user:write'])]
    private ?Company $company = null;

    /**
     * @var Collection<int, UserSystemEntityPermission>
     */
    #[ORM\OneToMany(targetEntity: UserSystemEntityPermission::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    #[Groups(['user:read'])]
    private Collection $systemEntityPermissions;

    #[ORM\ManyToOne]
    private ?Category $category = null;

    /**
     * @var Collection<int, UserGroup>
     */
    #[ORM\ManyToMany(targetEntity: UserGroup::class, inversedBy: 'users')]
    private Collection $userGroups;

    public function __construct()
    {
        parent::__construct();
        $this->projects = new ArrayCollection();
        $this->systemEntityPermissions = new ArrayCollection();
        $this->userGroups = new ArrayCollection();
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->email ?? '';
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = [];
        $userGroups = $this->getUserGroups();
        foreach ($userGroups as $userGroup) {
            $roles = array_merge($roles, $userGroup->getRoles());
        }
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection<int, Project>
     */
    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function addProject(Project $project): static
    {
        if (!$this->projects->contains($project)) {
            $this->projects->add($project);
            $project->setAssignee($this);
        }

        return $this;
    }

    public function removeProject(Project $project): static
    {
        if ($this->projects->removeElement($project)) {
            // set the owning side to null (unless already changed)
            if ($project->getAssignee() === $this) {
                $project->setAssignee(null);
            }
        }

        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;

        return $this;
    }

    /**
     * @return Collection<int, UserSystemEntityPermission>
     */
    public function getSystemEntityPermissions(): Collection
    {
        return $this->systemEntityPermissions;
    }

    public function addSystemEntityPermission(UserSystemEntityPermission $systemEntityPermission): static
    {
        if (!$this->systemEntityPermissions->contains($systemEntityPermission)) {
            $this->systemEntityPermissions->add($systemEntityPermission);
            $systemEntityPermission->setUser($this);
        }

        return $this;
    }

    public function removeSystemEntityPermission(UserSystemEntityPermission $systemEntityPermission): static
    {
        if ($this->systemEntityPermissions->removeElement($systemEntityPermission)) {
            // set the owning side to null (unless already changed)
            if ($systemEntityPermission->getUser() === $this) {
                $systemEntityPermission->setUser(null);
            }
        }

        return $this;
    }

    /**
     * Check if user has read access to a system entity.
     */
    public function hasReadAccessToSystemEntity(SystemEntity $systemEntity): bool
    {
        foreach ($this->systemEntityPermissions as $permission) {
            if ($permission->getSystemEntity() === $systemEntity && $permission->canRead()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has write access to a system entity.
     */
    public function hasWriteAccessToSystemEntity(SystemEntity $systemEntity): bool
    {
        foreach ($this->systemEntityPermissions as $permission) {
            if ($permission->getSystemEntity() === $systemEntity && $permission->canWrite()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all system entities user has read access to.
     *
     * @return SystemEntity[]
     */
    public function getReadableSystemEntities(): array
    {
        $systemEntities = [];
        foreach ($this->systemEntityPermissions as $permission) {
            if ($permission->canRead()) {
                $systemEntities[] = $permission->getSystemEntity();
            }
        }

        return $systemEntities;
    }

    /**
     * Get all system entities user has write access to.
     *
     * @return SystemEntity[]
     */
    public function getWritableSystemEntities(): array
    {
        $systemEntities = [];
        foreach ($this->systemEntityPermissions as $permission) {
            if ($permission->canWrite()) {
                $systemEntities[] = $permission->getSystemEntity();
            }
        }

        return $systemEntities;
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
     * @return Collection<int, UserGroup>
     */
    public function getUserGroups(): Collection
    {
        return $this->userGroups;
    }

    public function addUserGroup(UserGroup $userGroup): static
    {
        if (!$this->userGroups->contains($userGroup)) {
            $this->userGroups->add($userGroup);
        }

        return $this;
    }

    public function removeUserGroup(UserGroup $userGroup): static
    {
        $this->userGroups->removeElement($userGroup);

        return $this;
    }
}
