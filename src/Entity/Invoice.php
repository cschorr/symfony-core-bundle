<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use C3net\CoreBundle\Entity\Traits\Set\CategorizableTrait;
use C3net\CoreBundle\Enum\DomainEntityType;
use C3net\CoreBundle\Enum\InvoiceStatus;
use C3net\CoreBundle\Enum\InvoiceType;
use C3net\CoreBundle\Enum\PaymentStatus;
use C3net\CoreBundle\Repository\InvoiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
#[ApiResource(
    uriTemplate: '/transactions/{transactionId}/invoices',
    uriVariables: [
        'transactionId' => new Link(
            fromClass: Transaction::class,
            toProperty: 'transaction'
        ),
    ],
    operations: [
        new GetCollection(
            openapi: new Operation(tags: ['Transaction'])
        ),
    ],
    mercure: true,
    paginationEnabled: true,
    paginationClientEnabled: true,
    paginationClientItemsPerPage: true,
    paginationItemsPerPage: 30,
    paginationMaximumItemsPerPage: 100
)]
#[ApiResource(
    mercure: true,
    paginationClientEnabled: true,
    paginationClientItemsPerPage: true,
    paginationEnabled: true,
    paginationItemsPerPage: 30,
    paginationMaximumItemsPerPage: 100,
    operations: [
        new Get(),
        new GetCollection(
            parameters: [
                'invoiceNumber' => new QueryParameter(
                    filter: SearchFilter::class . ':invoiceNumber:partial'
                ),
                'status' => new QueryParameter(
                    filter: SearchFilter::class . ':status'
                ),
                'company' => new QueryParameter(
                    filter: SearchFilter::class . ':company'
                ),
                'project' => new QueryParameter(
                    filter: SearchFilter::class . ':project'
                ),
                'issueDate' => new QueryParameter(
                    filter: DateFilter::class . ':issueDate'
                ),
                'dueDate' => new QueryParameter(
                    filter: DateFilter::class . ':dueDate'
                ),
            ]
        ),
        new Post(),
        new Put(),
        new Patch(),
        new Delete(),
    ]
)]
#[ApiFilter(
    filterClass: OrderFilter::class,
    properties: [
        'invoiceNumber' => 'ASC',
        'invoiceDate' => 'DESC',
        'dueDate' => 'ASC',
    ],
)]
class Invoice extends AbstractEntity
{
    use CategorizableTrait;

    #[ORM\Column(type: Types::STRING, length: 50, unique: true)]
    #[Assert\NotBlank]
    private ?string $invoiceNumber = null;

    #[ORM\ManyToOne(inversedBy: 'invoices')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank]
    private ?Transaction $transaction = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: false, enumType: InvoiceType::class)]
    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'enum' => ['full', 'partial', 'deposit', 'final', 'credit_note'],
        ]
    )]
    private InvoiceType $invoiceType = InvoiceType::FULL;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: false, enumType: InvoiceStatus::class)]
    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'enum' => ['draft', 'sent', 'paid', 'overdue', 'cancelled'],
        ]
    )]
    private InvoiceStatus $status = InvoiceStatus::DRAFT;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: false, enumType: PaymentStatus::class)]
    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'enum' => ['unpaid', 'partial', 'paid', 'overdue'],
        ]
    )]
    private PaymentStatus $paymentStatus = PaymentStatus::UNPAID;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: false)]
    private ?\DateTimeImmutable $invoiceDate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dueDate = null;

    /** @var numeric-string */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: false)]
    private string $subtotal = '0.00';

    /** @var numeric-string */
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: false)]
    private string $taxRate = '19.00';

    /** @var numeric-string */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: false)]
    private string $taxAmount = '0.00';

    /** @var numeric-string */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: false)]
    private string $totalAmount = '0.00';

    /** @var numeric-string */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: false)]
    private string $paidAmount = '0.00';

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $paymentTerms = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\ManyToOne]
    private ?User $sentBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $paymentMethod = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $paymentReference = null;

    /**
     * @var Collection<int, InvoiceItem>
     */
    #[ORM\OneToMany(targetEntity: InvoiceItem::class, mappedBy: 'invoice', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $items;

    public function __construct()
    {
        parent::__construct();
        $this->items = new ArrayCollection();
        $this->invoiceDate = new \DateTimeImmutable();
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->invoiceNumber ?? 'Unnamed Invoice';
    }

    // Getters and Setters

    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }

    public function setInvoiceNumber(?string $invoiceNumber): static
    {
        $this->invoiceNumber = $invoiceNumber;

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

    public function getInvoiceType(): InvoiceType
    {
        return $this->invoiceType;
    }

    public function setInvoiceType(InvoiceType $invoiceType): static
    {
        $this->invoiceType = $invoiceType;

        return $this;
    }

    public function getStatus(): InvoiceStatus
    {
        return $this->status;
    }

    public function setStatus(InvoiceStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getPaymentStatus(): PaymentStatus
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(PaymentStatus $paymentStatus): static
    {
        $this->paymentStatus = $paymentStatus;

        return $this;
    }

    public function getInvoiceDate(): ?\DateTimeImmutable
    {
        return $this->invoiceDate;
    }

    public function setInvoiceDate(?\DateTimeImmutable $invoiceDate): static
    {
        $this->invoiceDate = $invoiceDate;

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
     * @return numeric-string
     */
    public function getSubtotal(): string
    {
        return $this->subtotal;
    }

    /**
     * @param numeric-string|int|float $subtotal
     */
    public function setSubtotal(int|float|string $subtotal): static
    {
        if (is_string($subtotal)) {
            /* @var numeric-string $subtotal */
            $this->subtotal = $subtotal;
        } else {
            // Convert int/float to string with 2 decimal places for DECIMAL column
            $this->subtotal = number_format((float) $subtotal, 2, '.', '');
        }

        return $this;
    }

    /**
     * @return numeric-string
     */
    public function getTaxRate(): string
    {
        return $this->taxRate;
    }

    /**
     * @param numeric-string|int|float $taxRate
     */
    public function setTaxRate(int|float|string $taxRate): static
    {
        if (is_string($taxRate)) {
            /* @var numeric-string $taxRate */
            $this->taxRate = $taxRate;
        } else {
            $this->taxRate = number_format((float) $taxRate, 2, '.', '');
        }

        return $this;
    }

    /**
     * @return numeric-string
     */
    public function getTaxAmount(): string
    {
        return $this->taxAmount;
    }

    /**
     * @param numeric-string|int|float $taxAmount
     */
    public function setTaxAmount(int|float|string $taxAmount): static
    {
        if (is_string($taxAmount)) {
            /* @var numeric-string $taxAmount */
            $this->taxAmount = $taxAmount;
        } else {
            $this->taxAmount = number_format((float) $taxAmount, 2, '.', '');
        }

        return $this;
    }

    /**
     * @return numeric-string
     */
    public function getTotalAmount(): string
    {
        return $this->totalAmount;
    }

    /**
     * @param numeric-string|int|float $totalAmount
     */
    public function setTotalAmount(int|float|string $totalAmount): static
    {
        if (is_string($totalAmount)) {
            /* @var numeric-string $totalAmount */
            $this->totalAmount = $totalAmount;
        } else {
            $this->totalAmount = number_format((float) $totalAmount, 2, '.', '');
        }

        return $this;
    }

    /**
     * @return numeric-string
     */
    public function getPaidAmount(): string
    {
        return $this->paidAmount;
    }

    /**
     * @param numeric-string|int|float $paidAmount
     */
    public function setPaidAmount(int|float|string $paidAmount): static
    {
        if (is_string($paidAmount)) {
            /* @var numeric-string $paidAmount */
            $this->paidAmount = $paidAmount;
        } else {
            $this->paidAmount = number_format((float) $paidAmount, 2, '.', '');
        }

        $this->updatePaymentStatus();

        return $this;
    }

    public function getPaymentTerms(): ?string
    {
        return $this->paymentTerms;
    }

    public function setPaymentTerms(?string $paymentTerms): static
    {
        $this->paymentTerms = $paymentTerms;

        return $this;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeImmutable $sentAt): static
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function getSentBy(): ?User
    {
        return $this->sentBy;
    }

    public function setSentBy(?User $sentBy): static
    {
        $this->sentBy = $sentBy;

        return $this;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTimeImmutable $paidAt): static
    {
        $this->paidAt = $paidAt;

        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getPaymentReference(): ?string
    {
        return $this->paymentReference;
    }

    public function setPaymentReference(?string $paymentReference): static
    {
        $this->paymentReference = $paymentReference;

        return $this;
    }

    /**
     * @return Collection<int, InvoiceItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(InvoiceItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setInvoice($this);
        }

        return $this;
    }

    public function removeItem(InvoiceItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getInvoice() === $this) {
                $item->setInvoice(null);
            }
        }

        return $this;
    }

    // Helper methods

    public function isDraft(): bool
    {
        return InvoiceStatus::DRAFT === $this->status;
    }

    public function isSent(): bool
    {
        return InvoiceStatus::SENT === $this->status;
    }

    public function isPaid(): bool
    {
        return InvoiceStatus::PAID === $this->status;
    }

    public function isOverdue(): bool
    {
        return InvoiceStatus::OVERDUE === $this->status;
    }

    public function isCancelled(): bool
    {
        return InvoiceStatus::CANCELLED === $this->status;
    }

    public function calculateTotals(): void
    {
        $subtotal = '0.00';
        foreach ($this->items as $item) {
            $subtotal = \bcadd($subtotal, $item->getTotalPrice(), 2);
        }

        $this->subtotal = $subtotal;
        $this->taxAmount = \bcmul($subtotal, \bcdiv($this->taxRate, '100', 4), 2);
        $this->totalAmount = \bcadd($subtotal, $this->taxAmount, 2);
    }

    private function updatePaymentStatus(): void
    {
        $comparison = \bccomp($this->paidAmount, $this->totalAmount, 2);

        if ($comparison >= 0) {
            $this->paymentStatus = PaymentStatus::PAID;
        } elseif (\bccomp($this->paidAmount, '0.00', 2) > 0) {
            $this->paymentStatus = PaymentStatus::PARTIAL;
        } else {
            $this->paymentStatus = PaymentStatus::UNPAID;
        }
    }

    public function checkOverdue(): void
    {
        if (
            null !== $this->dueDate
            && $this->dueDate < new \DateTimeImmutable()
            && PaymentStatus::PAID !== $this->paymentStatus
        ) {
            $this->status = InvoiceStatus::OVERDUE;
            $this->paymentStatus = PaymentStatus::OVERDUE;
        }
    }

    protected function getCategorizableEntityType(): DomainEntityType
    {
        return DomainEntityType::Invoice;
    }
}
