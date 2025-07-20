<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Entity\Traits\Set\SetCommunicationTrait;
use ApiPlatform\Metadata\ApiResource;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ApiResource(
    shortName: 'User',
    description: 'Represents a user in the system, with roles and permissions.'
)]
class User extends AbstractEntity implements UserInterface, PasswordAuthenticatedUserInterface
{
    use SetCommunicationTrait;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column(type: 'json')]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * @var Collection<int, Project>
     */
    #[ORM\OneToMany(targetEntity: Project::class, mappedBy: 'assignee')]
    private Collection $projects;

    #[ORM\ManyToOne(inversedBy: 'employees')]
    private ?Company $company = null;

    /**
     * @var Collection<int, UserModulePermission>
     */
    #[ORM\OneToMany(targetEntity: UserModulePermission::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $modulePermissions;

    public function __construct()
    {
        parent::__construct();
        $this->projects = new ArrayCollection();
        $this->modulePermissions = new ArrayCollection();
    }

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
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
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
     * @return Collection<int, UserModulePermission>
     */
    public function getModulePermissions(): Collection
    {
        return $this->modulePermissions;
    }

    public function addModulePermission(UserModulePermission $modulePermission): static
    {
        if (!$this->modulePermissions->contains($modulePermission)) {
            $this->modulePermissions->add($modulePermission);
            $modulePermission->setUser($this);
        }

        return $this;
    }

    public function removeModulePermission(UserModulePermission $modulePermission): static
    {
        if ($this->modulePermissions->removeElement($modulePermission)) {
            // set the owning side to null (unless already changed)
            if ($modulePermission->getUser() === $this) {
                $modulePermission->setUser(null);
            }
        }

        return $this;
    }

    /**
     * Check if user has read access to a module
     */
    public function hasReadAccessToModule(Module $module): bool
    {
        foreach ($this->modulePermissions as $permission) {
            if ($permission->getModule() === $module && $permission->canRead()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has write access to a module
     */
    public function hasWriteAccessToModule(Module $module): bool
    {
        foreach ($this->modulePermissions as $permission) {
            if ($permission->getModule() === $module && $permission->canWrite()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get all modules user has read access to
     * @return Module[]
     */
    public function getReadableModules(): array
    {
        $modules = [];
        foreach ($this->modulePermissions as $permission) {
            if ($permission->canRead()) {
                $modules[] = $permission->getModule();
            }
        }
        return $modules;
    }

    /**
     * Get all modules user has write access to
     * @return Module[]
     */
    public function getWritableModules(): array
    {
        $modules = [];
        foreach ($this->modulePermissions as $permission) {
            if ($permission->canWrite()) {
                $modules[] = $permission->getModule();
            }
        }
        return $modules;
    }
}
