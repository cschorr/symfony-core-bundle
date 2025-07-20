<?php

namespace App\Entity;

use App\Entity\Traits\Set\SetStartEndTrait;
use App\Entity\Traits\Single\StringNameTrait;
use App\Repository\ProjectRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
class Project extends AbstractEntity
{
    use StringNameTrait;
    use SetStartEndTrait;

    // Status constants for better readability
    public const STATUS_PLANNING = 0;
    public const STATUS_IN_PROGRESS = 1;
    public const STATUS_ON_HOLD = 2;
    public const STATUS_COMPLETED = 3;
    public const STATUS_CANCELLED = 4;

    #[ORM\Column(type: Types::INTEGER, nullable: false, options: ['default' => 0])]
    private int $status = self::STATUS_PLANNING;

    #[ORM\ManyToOne(inversedBy: 'projects')]
    private ?User $assignee = null;

    #[ORM\ManyToOne(inversedBy: 'projects')]
    private ?Company $client = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): static
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
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isPlanning(): bool
    {
        return $this->status === self::STATUS_PLANNING;
    }

    public function isOnHold(): bool
    {
        return $this->status === self::STATUS_ON_HOLD;
    }

    public function __toString(): string
    {
        return $this->getName() ?? 'Unnamed Project';
    }
}
