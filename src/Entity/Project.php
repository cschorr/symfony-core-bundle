<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use App\Entity\Traits\Set\SetStartEndTrait;
use App\Entity\Traits\Single\StringNameTrait;
use App\Enum\ProjectStatus;
use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ApiResource(
    mercure: true,
    paginationClientEnabled: true,
    paginationClientItemsPerPage: true,
    paginationEnabled: true,
    paginationItemsPerPage: 30,
    paginationMaximumItemsPerPage: 100,
)]
#[ApiFilter(
    filterClass: OrderFilter::class,
    properties: [
        'title' => 'ASC',
        'year' => 'DESC',
    ],
)]
class Project extends AbstractEntity
{
    use StringNameTrait;
    use SetStartEndTrait;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: false, enumType: ProjectStatus::class)]
    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'enum' => ['planning', 'in_progress', 'on_hold', 'completed', 'cancelled'],
        ]
    )]
    private ProjectStatus $status = ProjectStatus::PLANNING;

    public function getStatus(): ProjectStatus
    {
        return $this->status;
    }

    public function setStatus(ProjectStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    #[ORM\ManyToOne(inversedBy: 'projects')]
    private ?User $assignee = null;

    #[ORM\ManyToOne(inversedBy: 'projects')]
    private ?Company $client = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne]
    private ?Category $category = null;

    /**
     * @var Collection<int, Notification>
     */
    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'project')]
    private Collection $notifications;

    #[ORM\ManyToOne(inversedBy: 'projects')]
    private ?Campaign $campaign = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dueDate = null;

    /**
     * @var Collection<int, Contact>
     */
    #[ORM\ManyToMany(targetEntity: Contact::class, inversedBy: 'projects')]
    private Collection $contact;

    public function __construct()
    {
        parent::__construct();
        $this->notifications = new ArrayCollection();
        $this->contact = new ArrayCollection();
    }

    // Fixed status helper methods
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

    #[\Override]
    public function __toString(): string
    {
        return $this->getName() ?: 'Unnamed Project';
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
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setProject($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getProject() === $this) {
                $notification->setProject(null);
            }
        }

        return $this;
    }

    public function getCampaign(): ?Campaign
    {
        return $this->campaign;
    }

    public function setCampaign(?Campaign $campaign): static
    {
        $this->campaign = $campaign;

        return $this;
    }

    public function getDueDate(): ?\DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTimeImmutable $dueDate): static
    {
        $this->dueDate = $dueDate;

        return $this;
    }

    /**
     * @return Collection<int, Contact>
     */
    public function getContact(): Collection
    {
        return $this->contact;
    }

    public function addContact(Contact $contact): static
    {
        if (!$this->contact->contains($contact)) {
            $this->contact->add($contact);
        }

        return $this;
    }

    public function removeContact(Contact $contact): static
    {
        $this->contact->removeElement($contact);

        return $this;
    }
}
