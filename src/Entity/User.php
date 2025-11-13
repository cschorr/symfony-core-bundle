<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use C3net\CoreBundle\Entity\Traits\Set\CategorizableTrait;
use C3net\CoreBundle\Entity\Traits\Set\SetCommunicationTrait;
use C3net\CoreBundle\Entity\Traits\Set\SetNamePersonTrait;
use C3net\CoreBundle\Enum\DomainEntityType;
use C3net\CoreBundle\Enum\UserRole;
use C3net\CoreBundle\Repository\UserRepository;
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
)]
class User extends AbstractEntity implements UserInterface, PasswordAuthenticatedUserInterface
{
    use SetCommunicationTrait;
    use SetNamePersonTrait;
    use CategorizableTrait;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column]
    private ?string $password = null;

    /**
     * @var Collection<int, Project>
     */
    #[ORM\OneToMany(targetEntity: Project::class, mappedBy: 'assignee')]
    private Collection $projects;

    #[ORM\ManyToOne]
    private ?Company $company = null;

    /**
     * @var Collection<int, UserGroup>
     */
    #[ORM\ManyToMany(targetEntity: UserGroup::class, mappedBy: 'users')]
    private Collection $userGroups;

    #[ORM\Column(nullable: false, options: ['default' => false])]
    private bool $locked = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastLogin = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $passwordResetToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $passwordResetTokenExpiresAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $passwordChangedAt = null;

    // Transient field to store old password hash for change detection
    private ?string $oldPasswordHash = null;

    // Stored as list of strings in DB; use helper methods to work with UserRole enums.
    /** @var list<string>|null */
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::JSON, nullable: true)]
    private ?array $roles = null;

    /**
     * @var Collection<int, AuditLogs>
     */
    #[ORM\OneToMany(targetEntity: AuditLogs::class, mappedBy: 'author')]
    private Collection $auditLogs;

    public function __construct()
    {
        parent::__construct();
        $this->projects = new ArrayCollection();
        $this->userGroups = new ArrayCollection();
        $this->auditLogs = new ArrayCollection();
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
     * @return non-empty-string
     */
    public function getUserIdentifier(): string
    {
        if (null === $this->email || '' === $this->email) {
            throw new \LogicException('User email must be set to get user identifier');
        }

        return $this->email;
    }

    /**
     * Security-compatible roles as strings.
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles ?? [];

        foreach ($this->getUserGroups() as $userGroup) {
            $roles = array_merge($roles, $userGroup->getRoles()); // strings
        }

        $roles[] = UserRole::ROLE_USER->value;

        return array_values(array_unique($roles));
    }

    /**
     * Replace direct user roles with enums.
     *
     * @param list<UserRole> $roles
     */
    public function setRolesFromEnums(array $roles): static
    {
        $this->roles = array_values(array_unique(array_map(static fn (UserRole $r) => $r->value, $roles)));

        return $this;
    }

    /**
     * Read direct user roles as enums (does not include roles from groups).
     *
     * @return list<UserRole>
     */
    public function getRoleEnums(): array
    {
        $stored = $this->roles ?? [];

        return array_map(UserRole::from(...), $stored);
    }

    /**
     * Backward-compatible setter accepting strings or enums.
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

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function eraseCredentials(): void
    {
        // noop
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

    protected function getCategorizableEntityType(): DomainEntityType
    {
        return DomainEntityType::User;
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
        // Delegate to the owning side (UserGroup)
        $userGroup->addUser($this);

        return $this;
    }

    public function removeUserGroup(UserGroup $userGroup): static
    {
        // Delegate to the owning side (UserGroup)
        $userGroup->removeUser($this);

        return $this;
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }

    public function setLocked(bool $locked): static
    {
        $this->locked = $locked;

        return $this;
    }

    public function getLastLogin(): ?\DateTimeImmutable
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?\DateTimeImmutable $lastLogin): static
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    public function getPasswordResetToken(): ?string
    {
        return $this->passwordResetToken;
    }

    public function setPasswordResetToken(?string $passwordResetToken): static
    {
        $this->passwordResetToken = $passwordResetToken;

        return $this;
    }

    public function getPasswordResetTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->passwordResetTokenExpiresAt;
    }

    public function setPasswordResetTokenExpiresAt(?\DateTimeImmutable $passwordResetTokenExpiresAt): static
    {
        $this->passwordResetTokenExpiresAt = $passwordResetTokenExpiresAt;

        return $this;
    }

    public function getPasswordChangedAt(): ?\DateTimeImmutable
    {
        return $this->passwordChangedAt;
    }

    public function setPasswordChangedAt(?\DateTimeImmutable $passwordChangedAt): static
    {
        $this->passwordChangedAt = $passwordChangedAt;

        return $this;
    }

    public function getOldPasswordHash(): ?string
    {
        return $this->oldPasswordHash;
    }

    public function setOldPasswordHash(?string $oldPasswordHash): static
    {
        $this->oldPasswordHash = $oldPasswordHash;

        return $this;
    }

    /**
     * @return Collection<int, AuditLogs>
     */
    public function getAuditLogs(): Collection
    {
        return $this->auditLogs;
    }

    public function addAuditLog(AuditLogs $auditLog): static
    {
        if (!$this->auditLogs->contains($auditLog)) {
            $this->auditLogs->add($auditLog);
            $auditLog->setAuthor($this);
        }

        return $this;
    }

    public function removeAuditLog(AuditLogs $auditLog): static
    {
        if ($this->auditLogs->removeElement($auditLog)) {
            // set the owning side to null (unless already changed)
            if ($auditLog->getAuthor() === $this) {
                $auditLog->setAuthor(null);
            }
        }

        return $this;
    }

    // API Platform serialization methods
    #[Groups(['user:read'])]
    public function getIdString(): ?string
    {
        return $this->getId()?->toString();
    }

    #[Groups(['user:read'])]
    public function getIsActive(): bool
    {
        return $this->isActive();
    }

    #[Groups(['user:read'])]
    public function getIsLocked(): bool
    {
        return $this->isLocked();
    }

    #[Groups(['user:read'])]
    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }
}
