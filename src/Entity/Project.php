<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Traits\Set\SetStartEndTrait;
use App\Entity\Traits\Single\StringNameTrait;
use App\Enum\ProjectStatus;
use App\Repository\ProjectRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ApiResource]
class Project extends AbstractEntity
{
    use StringNameTrait;
    use SetStartEndTrait;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    private string $status = ProjectStatus::PLANNING->value;

    #[ORM\ManyToOne(inversedBy: 'projects')]
    private ?User $assignee = null;

    #[ORM\ManyToOne(inversedBy: 'projects')]
    private ?Company $client = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne]
    private ?Category $category = null;

    public function getStatus(): ProjectStatus
    {
        return ProjectStatus::from($this->status);
    }

    public function setStatus(ProjectStatus $status): static
    {
        $this->status = $status->value;

        return $this;
    }

    public function getAssignee(): ?User
    {
        return $this->assignee;
    }

    public function setAssignee(?User $assignee): static
    {
        $this->assignee = $assignee;

        return $this;
    }

    public function getClient(): ?Company
    {
        return $this->client;
    }

    public function setClient(?Company $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    // Status helper methods
    public function isInProgress(): bool
    {
        return ProjectStatus::IN_PROGRESS === $this->status;
    }

    public function isCompleted(): bool
    {
        return ProjectStatus::COMPLETED === $this->status;
    }

    public function isCancelled(): bool
    {
        return ProjectStatus::CANCELLED === $this->status;
    }

    public function isPlanning(): bool
    {
        return ProjectStatus::PLANNING === $this->status;
    }

    public function isOnHold(): bool
    {
        return ProjectStatus::ON_HOLD === $this->status;
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->getName() ?? 'Unnamed Project';
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
}
