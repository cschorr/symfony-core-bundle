<?php

namespace App\Entity;

use App\Entity\Traits\Set\SetStartEndTrait;
use App\Entity\Traits\Single\StringNameTrait;
use App\Enum\ProjectStatus;
use App\Repository\ProjectRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
class Project extends AbstractEntity
{
    use StringNameTrait;
    use SetStartEndTrait;

    #[ORM\Column(enumType: ProjectStatus::class, nullable: false, options: ['default' => 0])]
    private ProjectStatus $status = ProjectStatus::PLANNING;

    #[ORM\ManyToOne(inversedBy: 'projects')]
    private ?User $assignee = null;

    #[ORM\ManyToOne(inversedBy: 'projects')]
    private ?Company $client = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    public function getStatus(): ProjectStatus
    {
        return $this->status;
    }

    public function setStatus(ProjectStatus $status): static
    {
        $this->status = $status;

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
        return $this->status === ProjectStatus::IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return $this->status === ProjectStatus::COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === ProjectStatus::CANCELLED;
    }

    public function isPlanning(): bool
    {
        return $this->status === ProjectStatus::PLANNING;
    }

    public function isOnHold(): bool
    {
        return $this->status === ProjectStatus::ON_HOLD;
    }

    public function __toString(): string
    {
        return $this->getName() ?? 'Unnamed Project';
    }
}
