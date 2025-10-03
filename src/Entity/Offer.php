<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use C3net\CoreBundle\Enum\OfferStatus;
use C3net\CoreBundle\Repository\OfferRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OfferRepository::class)]
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
        'offerNumber' => 'ASC',
        'createdAt' => 'DESC',
        'version' => 'DESC',
    ],
)]
#[ApiFilter(
    filterClass: SearchFilter::class,
    properties: [
        'transaction' => 'exact',
        'status' => 'exact',
    ],
)]
class Offer extends AbstractEntity
{
    #[ORM\Column(type: Types::STRING, length: 50, unique: true)]
    #[Assert\NotBlank]
    private ?string $offerNumber = null;

    #[ORM\ManyToOne(inversedBy: 'offers')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank]
    private ?Transaction $transaction = null;

    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    private int $version = 1;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: false, enumType: OfferStatus::class)]
    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'enum' => ['draft', 'sent', 'accepted', 'rejected', 'expired'],
        ]
    )]
    private OfferStatus $status = OfferStatus::DRAFT;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $validUntil = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: false)]
    private string $subtotal = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: false)]
    private string $taxRate = '19.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: false)]
    private string $taxAmount = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: false)]
    private string $totalAmount = '0.00';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $terms = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $customerNotes = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\ManyToOne]
    private ?User $sentBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $acceptedAt = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $acceptedBy = null;

    /**
     * @var Collection<int, OfferItem>
     */
    #[ORM\OneToMany(targetEntity: OfferItem::class, mappedBy: 'offer', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $items;

    public function __construct()
    {
        parent::__construct();
        $this->items = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->offerNumber ?? 'Unnamed Offer';
    }

    // Getters and Setters

    public function getOfferNumber(): ?string
    {
        return $this->offerNumber;
    }

    public function setOfferNumber(?string $offerNumber): static
    {
        $this->offerNumber = $offerNumber;

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

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): static
    {
        $this->version = $version;

        return $this;
    }

    public function getStatus(): OfferStatus
    {
        return $this->status;
    }

    public function setStatus(OfferStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getValidUntil(): ?\DateTimeImmutable
    {
        return $this->validUntil;
    }

    public function setValidUntil(?\DateTimeImmutable $validUntil): static
    {
        $this->validUntil = $validUntil;

        return $this;
    }

    public function getSubtotal(): string
    {
        return $this->subtotal;
    }

    public function setSubtotal(string $subtotal): static
    {
        $this->subtotal = $subtotal;

        return $this;
    }

    public function getTaxRate(): string
    {
        return $this->taxRate;
    }

    public function setTaxRate(string $taxRate): static
    {
        $this->taxRate = $taxRate;

        return $this;
    }

    public function getTaxAmount(): string
    {
        return $this->taxAmount;
    }

    public function setTaxAmount(string $taxAmount): static
    {
        $this->taxAmount = $taxAmount;

        return $this;
    }

    public function getTotalAmount(): string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(string $totalAmount): static
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    public function getTerms(): ?string
    {
        return $this->terms;
    }

    public function setTerms(?string $terms): static
    {
        $this->terms = $terms;

        return $this;
    }

    public function getCustomerNotes(): ?string
    {
        return $this->customerNotes;
    }

    public function setCustomerNotes(?string $customerNotes): static
    {
        $this->customerNotes = $customerNotes;

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

    public function getAcceptedAt(): ?\DateTimeImmutable
    {
        return $this->acceptedAt;
    }

    public function setAcceptedAt(?\DateTimeImmutable $acceptedAt): static
    {
        $this->acceptedAt = $acceptedAt;

        return $this;
    }

    public function getAcceptedBy(): ?string
    {
        return $this->acceptedBy;
    }

    public function setAcceptedBy(?string $acceptedBy): static
    {
        $this->acceptedBy = $acceptedBy;

        return $this;
    }

    /**
     * @return Collection<int, OfferItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(OfferItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setOffer($this);
        }

        return $this;
    }

    public function removeItem(OfferItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getOffer() === $this) {
                $item->setOffer(null);
            }
        }

        return $this;
    }

    // Helper methods

    public function isDraft(): bool
    {
        return OfferStatus::DRAFT === $this->status;
    }

    public function isSent(): bool
    {
        return OfferStatus::SENT === $this->status;
    }

    public function isAccepted(): bool
    {
        return OfferStatus::ACCEPTED === $this->status;
    }

    public function isRejected(): bool
    {
        return OfferStatus::REJECTED === $this->status;
    }

    public function isExpired(): bool
    {
        return OfferStatus::EXPIRED === $this->status;
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
}
