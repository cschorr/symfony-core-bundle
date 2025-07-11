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

    // Status-Konstanten für bessere Lesbarkeit
    public const STATUS_DRAFT = 'draft';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_REVIEW = 'review';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    #[ORM\Column(type: Types::STRING, length: 50, nullable: false, options: ['default' => 'draft'])]
    private string $status = self::STATUS_DRAFT;

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

    // Hilfsmethoden für den Status
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

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isInReview(): bool
    {
        return $this->status === self::STATUS_REVIEW;
    }
}
