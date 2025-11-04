<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use C3net\CoreBundle\Entity\Traits\Set\CategorizableTrait;
use C3net\CoreBundle\Entity\Traits\Set\SetStartEndTrait;
use C3net\CoreBundle\Entity\Traits\Single\StringNameTrait;
use C3net\CoreBundle\Enum\BillingStatus;
use C3net\CoreBundle\Enum\DomainEntityType;
use C3net\CoreBundle\Enum\ProjectPriority;
use C3net\CoreBundle\Enum\ProjectStatus;
use C3net\CoreBundle\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'project_type', type: 'string')]
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
        'name' => 'ASC',
        'dueDate' => 'DESC',
        'status' => 'ASC',
        'priority' => 'DESC',
    ],
)]
#[ApiFilter(
    filterClass: SearchFilter::class,
    properties: [
        'name' => 'partial',
        'assignee' => 'exact',
        'status' => 'exact',
        'priority' => 'exact',
    ],
)]
class Project extends AbstractEntity
{
    use StringNameTrait;
    use SetStartEndTrait;
    use CategorizableTrait;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: false, enumType: ProjectStatus::class)]
    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'enum' => ['planning', 'in_progress', 'on_hold', 'completed', 'cancelled'],
        ]
    )]
    private ProjectStatus $status = ProjectStatus::PLANNING;

    /**
     * Get status as string value.
     *
     * Returns the string representation of the status for workflow compatibility
     * and API serialization. Use getStatusEnum() for type-safe access to the enum.
     *
     * @return string The status value (e.g., 'planning', 'in_progress', 'completed')
     */
    public function getStatus(): string
    {
        return $this->status->value;
    }

    /**
     * Get status as enum for type-safe operations.
     *
     * Use this method when you need the enum instance for type-safe comparisons
     * or when working with status-specific logic.
     *
     * @return ProjectStatus The status enum instance
     */
    public function getStatusEnum(): ProjectStatus
    {
        return $this->status;
    }

    /**
     * Set status from enum or string value.
     *
     * Accepts both enum instances (for type-safe code) and string values
     * (for Symfony Workflow integration and API input).
     *
     * @param ProjectStatus|string $status Status enum or string value
     *
     * @throws \ValueError if string value is not a valid status
     */
    public function setStatus(ProjectStatus|string $status): static
    {
        if (is_string($status)) {
            $this->status = ProjectStatus::from($status);
        } else {
            $this->status = $status;
        }

        return $this;
    }

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true, enumType: ProjectPriority::class)]
    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'enum' => ['medium', 'high', 'critical'],
        ]
    )]
    private ?ProjectPriority $priority = null;

    /**
     * Get priority as string value.
     *
     * Returns the string representation of the priority for API serialization.
     * Use getPriorityEnum() for type-safe access to the enum.
     *
     * @return string|null The priority value (e.g., 'low', 'medium', 'high')
     */
    public function getPriority(): ?string
    {
        return $this->priority?->value;
    }

    /**
     * Get priority as enum for type-safe operations.
     *
     * Use this method when you need the enum instance for type-safe comparisons
     * or when working with priority-specific logic.
     *
     * @return ProjectPriority|null The priority enum instance
     */
    public function getPriorityEnum(): ?ProjectPriority
    {
        return $this->priority;
    }

    /**
     * Set priority from enum or string value.
     *
     * Accepts both enum instances (for type-safe code) and string values
     * (for API input).
     *
     * @param ProjectPriority|string|null $priority Priority enum or string value
     *
     * @throws \ValueError if string value is not a valid priority
     */
    public function setPriority(ProjectPriority|string|null $priority): static
    {
        if (null === $priority) {
            $this->priority = null;
        } elseif (is_string($priority)) {
            $this->priority = ProjectPriority::from($priority);
        } else {
            $this->priority = $priority;
        }

        return $this;
    }

    #[ORM\ManyToOne(inversedBy: 'projects')]
    private ?User $assignee = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * @var Collection<int, Notification>
     */
    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'project')]
    private Collection $notifications;

    #[ORM\ManyToOne(inversedBy: 'projects')]
    private ?Campaign $campaign = null;

    #[ORM\ManyToOne(inversedBy: 'projects')]
    private ?Transaction $transaction = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dueDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $estimatedHours = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $actualHours = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $estimatedCost = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $actualCost = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true, enumType: BillingStatus::class)]
    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'enum' => ['not_billed', 'billed', 'paid'],
        ]
    )]
    private ?BillingStatus $billingStatus = null;

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

    /**
     * Get the customer company from the associated transaction.
     * This is a convenience method that accesses the customer through the transaction relationship.
     *
     * @return Company|null The customer company, or null if no transaction is associated
     */
    public function getCustomer(): ?Company
    {
        return $this->transaction?->getCustomer();
    }

    /**
     * Get the customer company name from the associated transaction.
     * This is a virtual property for API responses - not stored in database.
     * Computed on-the-fly from the transaction's customer relationship.
     * Concatenates company name and nameExtension with a space.
     *
     * @return string|null The customer company name (name + nameExtension), or null if no transaction/customer is associated
     */
    #[ApiProperty(readable: true, writable: false)]
    public function getCustomerName(): ?string
    {
        $customer = $this->getCustomer();
        if ($customer === null) {
            return null;
        }

        $name = $customer->getName();
        $extension = $customer->getNameExtension();

        if ($extension !== null && $extension !== '' && $extension !== '0') {
            return $name . ' ' . $extension;
        }

        return $name;
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
        return !in_array($this->getName(), ['', '0'], true) ? $this->getName() : 'Unnamed Project';
    }

    protected function getCategorizableEntityType(): DomainEntityType
    {
        return DomainEntityType::Project;
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

    public function getTransaction(): ?Transaction
    {
        return $this->transaction;
    }

    public function setTransaction(?Transaction $transaction): static
    {
        $this->transaction = $transaction;

        return $this;
    }

    public function getEstimatedHours(): ?string
    {
        return $this->estimatedHours;
    }

    public function setEstimatedHours(?string $estimatedHours): static
    {
        $this->estimatedHours = $estimatedHours;

        return $this;
    }

    public function getActualHours(): ?string
    {
        return $this->actualHours;
    }

    public function setActualHours(?string $actualHours): static
    {
        $this->actualHours = $actualHours;

        return $this;
    }

    public function getEstimatedCost(): ?string
    {
        return $this->estimatedCost;
    }

    public function setEstimatedCost(?string $estimatedCost): static
    {
        $this->estimatedCost = $estimatedCost;

        return $this;
    }

    public function getActualCost(): ?string
    {
        return $this->actualCost;
    }

    public function setActualCost(?string $actualCost): static
    {
        $this->actualCost = $actualCost;

        return $this;
    }

    public function getBillingStatus(): ?BillingStatus
    {
        return $this->billingStatus;
    }

    public function setBillingStatus(?BillingStatus $billingStatus): static
    {
        $this->billingStatus = $billingStatus;

        return $this;
    }

    // Helper methods for billing status
    public function isNotBilled(): bool
    {
        return BillingStatus::NOT_BILLED === $this->billingStatus;
    }

    public function isBilled(): bool
    {
        return BillingStatus::BILLED === $this->billingStatus;
    }

    public function isBillingPaid(): bool
    {
        return BillingStatus::PAID === $this->billingStatus;
    }

    // Helper methods for priority
    public function isMediumPriority(): bool
    {
        return ProjectPriority::MEDIUM === $this->getPriorityEnum();
    }

    public function isHighPriority(): bool
    {
        return ProjectPriority::HIGH === $this->getPriorityEnum();
    }

    public function isCritical(): bool
    {
        return ProjectPriority::CRITICAL === $this->getPriorityEnum();
    }
}
