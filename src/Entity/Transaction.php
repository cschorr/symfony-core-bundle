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
use C3net\CoreBundle\Entity\Traits\Single\StringShortcodeTrait;
use C3net\CoreBundle\Enum\DomainEntityType;
use C3net\CoreBundle\Enum\TransactionStatus;
use C3net\CoreBundle\Enum\TransactionType;
use C3net\CoreBundle\Repository\TransactionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap([
    'transaction' => Transaction::class,
    'app_transaction' => 'App\\Entity\\Transaction',
])]
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
        'transactionNumber' => 'ASC',
        'createdAt' => 'DESC',
        'totalValue' => 'DESC',
    ],
)]
#[ApiFilter(
    filterClass: SearchFilter::class,
    properties: [
        'customer' => 'exact',
        'status' => 'exact',
        'transactionType' => 'exact',
        'assignedTo' => 'exact',
    ],
)]
class Transaction extends AbstractEntity
{
    use StringNameTrait;
    use StringShortcodeTrait;
    use SetStartEndTrait;
    use CategorizableTrait;

    #[ORM\Column(type: Types::STRING, length: 50, unique: true)]
    #[Assert\NotBlank]
    private ?string $transactionNumber = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: false, enumType: TransactionType::class)]
    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'enum' => ['quote', 'order', 'service', 'retainer', 'project', 'other'],
        ]
    )]
    private TransactionType $transactionType = TransactionType::ORDER;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: false, enumType: TransactionStatus::class)]
    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'enum' => ['draft', 'quoted', 'ordered', 'in_production', 'delivered', 'invoiced', 'paid', 'cancelled'],
        ]
    )]
    private TransactionStatus $status = TransactionStatus::DRAFT;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank]
    private ?Company $customer = null;

    #[ORM\ManyToOne]
    private ?Contact $primaryContact = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $totalValue = null;

    #[ORM\Column(type: Types::STRING, length: 3, nullable: false)]
    private string $currency = 'EUR';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $internalNotes = null;

    #[ORM\ManyToOne]
    private ?User $assignedTo = null;

    /**
     * @var Collection<int, Offer>
     */
    #[ORM\OneToMany(targetEntity: Offer::class, mappedBy: 'transaction', cascade: ['persist', 'remove'])]
    private Collection $offers;

    /**
     * @var Collection<int, Invoice>
     */
    #[ORM\OneToMany(targetEntity: Invoice::class, mappedBy: 'transaction', cascade: ['persist', 'remove'])]
    private Collection $invoices;

    /**
     * @var Collection<int, Campaign>
     */
    #[ORM\OneToMany(targetEntity: Campaign::class, mappedBy: 'transaction')]
    private Collection $campaigns;

    /**
     * @var Collection<int, Project>
     */
    #[ORM\OneToMany(targetEntity: Project::class, mappedBy: 'transaction')]
    private Collection $projects;

    /**
     * @var Collection<int, Contact>
     */
    #[ORM\ManyToMany(targetEntity: Contact::class)]
    #[ORM\JoinTable(name: 'transaction_contact')]
    private Collection $contacts;

    /**
     * @var Collection<int, Document>
     */
    #[ORM\OneToMany(targetEntity: Document::class, mappedBy: 'transaction', cascade: ['persist', 'remove'])]
    private Collection $documents;

    public function __construct()
    {
        parent::__construct();
        $this->offers = new ArrayCollection();
        $this->invoices = new ArrayCollection();
        $this->campaigns = new ArrayCollection();
        $this->projects = new ArrayCollection();
        $this->contacts = new ArrayCollection();
        $this->documents = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->transactionNumber ?? $this->getName() ?: 'Unnamed Transaction';
    }

    // Getters and Setters

    public function getTransactionNumber(): ?string
    {
        return $this->transactionNumber;
    }

    public function setTransactionNumber(?string $transactionNumber): static
    {
        $this->transactionNumber = $transactionNumber;

        return $this;
    }

    public function getTransactionType(): TransactionType
    {
        return $this->transactionType;
    }

    public function setTransactionType(TransactionType $transactionType): static
    {
        $this->transactionType = $transactionType;

        return $this;
    }

    /**
     * Get status as string value.
     *
     * Returns the string representation of the status for workflow compatibility
     * and API serialization. Use getStatusEnum() for type-safe access to the enum.
     *
     * @return string The status value (e.g., 'draft', 'quoted', 'ordered')
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
     * @return TransactionStatus The status enum instance
     */
    public function getStatusEnum(): TransactionStatus
    {
        return $this->status;
    }

    /**
     * Set status from enum or string value.
     *
     * Accepts both enum instances (for type-safe code) and string values
     * (for Symfony Workflow integration and API input).
     *
     * @param TransactionStatus|string $status Status enum or string value
     *
     * @throws \ValueError if string value is not a valid status
     */
    public function setStatus(TransactionStatus|string $status): static
    {
        if (is_string($status)) {
            $this->status = TransactionStatus::from($status);
        } else {
            $this->status = $status;
        }

        return $this;
    }

    public function getCustomer(): ?Company
    {
        return $this->customer;
    }

    public function setCustomer(?Company $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getPrimaryContact(): ?Contact
    {
        return $this->primaryContact;
    }

    public function setPrimaryContact(?Contact $primaryContact): static
    {
        $this->primaryContact = $primaryContact;

        return $this;
    }

    public function getTotalValue(): ?string
    {
        return $this->totalValue;
    }

    public function setTotalValue(?string $totalValue): static
    {
        $this->totalValue = $totalValue;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

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

    public function getInternalNotes(): ?string
    {
        return $this->internalNotes;
    }

    public function setInternalNotes(?string $internalNotes): static
    {
        $this->internalNotes = $internalNotes;

        return $this;
    }

    public function getAssignedTo(): ?User
    {
        return $this->assignedTo;
    }

    public function setAssignedTo(?User $assignedTo): static
    {
        $this->assignedTo = $assignedTo;

        return $this;
    }

    protected function getCategorizableEntityType(): DomainEntityType
    {
        return DomainEntityType::Transaction;
    }

    /**
     * @return Collection<int, Offer>
     */
    public function getOffers(): Collection
    {
        return $this->offers;
    }

    public function addOffer(Offer $offer): static
    {
        if (!$this->offers->contains($offer)) {
            $this->offers->add($offer);
            $offer->setTransaction($this);
        }

        return $this;
    }

    public function removeOffer(Offer $offer): static
    {
        if ($this->offers->removeElement($offer)) {
            if ($offer->getTransaction() === $this) {
                $offer->setTransaction(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Invoice>
     */
    public function getInvoices(): Collection
    {
        return $this->invoices;
    }

    public function addInvoice(Invoice $invoice): static
    {
        if (!$this->invoices->contains($invoice)) {
            $this->invoices->add($invoice);
            $invoice->setTransaction($this);
        }

        return $this;
    }

    public function removeInvoice(Invoice $invoice): static
    {
        if ($this->invoices->removeElement($invoice)) {
            if ($invoice->getTransaction() === $this) {
                $invoice->setTransaction(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Campaign>
     */
    public function getCampaigns(): Collection
    {
        return $this->campaigns;
    }

    public function addCampaign(Campaign $campaign): static
    {
        if (!$this->campaigns->contains($campaign)) {
            $this->campaigns->add($campaign);
            $campaign->setTransaction($this);
        }

        return $this;
    }

    public function removeCampaign(Campaign $campaign): static
    {
        if ($this->campaigns->removeElement($campaign)) {
            if ($campaign->getTransaction() === $this) {
                $campaign->setTransaction(null);
            }
        }

        return $this;
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
            $project->setTransaction($this);
        }

        return $this;
    }

    public function removeProject(Project $project): static
    {
        if ($this->projects->removeElement($project)) {
            if ($project->getTransaction() === $this) {
                $project->setTransaction(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Contact>
     */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }

    public function addContact(Contact $contact): static
    {
        if (!$this->contacts->contains($contact)) {
            $this->contacts->add($contact);
        }

        return $this;
    }

    public function removeContact(Contact $contact): static
    {
        $this->contacts->removeElement($contact);

        return $this;
    }

    /**
     * @return Collection<int, Document>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(Document $document): static
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setTransaction($this);
        }

        return $this;
    }

    public function removeDocument(Document $document): static
    {
        if ($this->documents->removeElement($document)) {
            if ($document->getTransaction() === $this) {
                $document->setTransaction(null);
            }
        }

        return $this;
    }

    // Helper methods

    public function isDraft(): bool
    {
        return TransactionStatus::DRAFT === $this->status;
    }

    public function isQuoted(): bool
    {
        return TransactionStatus::QUOTED === $this->status;
    }

    public function isOrdered(): bool
    {
        return TransactionStatus::ORDERED === $this->status;
    }

    public function isInProduction(): bool
    {
        return TransactionStatus::IN_PRODUCTION === $this->status;
    }

    public function isDelivered(): bool
    {
        return TransactionStatus::DELIVERED === $this->status;
    }

    public function isInvoiced(): bool
    {
        return TransactionStatus::INVOICED === $this->status;
    }

    public function isPaid(): bool
    {
        return TransactionStatus::PAID === $this->status;
    }

    public function isCancelled(): bool
    {
        return TransactionStatus::CANCELLED === $this->status;
    }
}
