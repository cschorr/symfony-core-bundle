<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use C3net\CoreBundle\Repository\InvoiceItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: InvoiceItemRepository::class)]
#[ApiResource(
    mercure: true,
)]
class InvoiceItem extends AbstractEntity
{
    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank]
    private ?Invoice $invoice = null;

    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    private int $position = 0;

    #[ORM\Column(type: Types::STRING, length: 500)]
    #[Assert\NotBlank]
    private ?string $description = null;

    /** @var numeric-string */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: false)]
    #[Assert\GreaterThan(0)]
    private string $quantity = '1.00';

    #[ORM\Column(type: Types::STRING, length: 50, nullable: false)]
    private string $unit = 'piece';

    /** @var numeric-string */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: false)]
    private string $unitPrice = '0.00';

    /** @var numeric-string */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: false)]
    private string $totalPrice = '0.00';

    #[ORM\ManyToOne]
    private ?Project $project = null;

    #[\Override]
    public function __toString(): string
    {
        return $this->description ?? 'Unnamed Item';
    }

    // Getters and Setters

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(?Invoice $invoice): static
    {
        $this->invoice = $invoice;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

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

    /**
     * @return numeric-string
     */
    public function getQuantity(): string
    {
        return $this->quantity;
    }

    /**
     * @param numeric-string $quantity
     */
    public function setQuantity(string $quantity): static
    {
        /* @var numeric-string $quantity */
        $this->quantity = $quantity;
        $this->calculateTotalPrice();

        return $this;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function setUnit(string $unit): static
    {
        $this->unit = $unit;

        return $this;
    }

    /**
     * @return numeric-string
     */
    public function getUnitPrice(): string
    {
        return $this->unitPrice;
    }

    /**
     * @param numeric-string $unitPrice
     */
    public function setUnitPrice(string $unitPrice): static
    {
        /* @var numeric-string $unitPrice */
        $this->unitPrice = $unitPrice;
        $this->calculateTotalPrice();

        return $this;
    }

    /**
     * @return numeric-string
     */
    public function getTotalPrice(): string
    {
        return $this->totalPrice;
    }

    /**
     * @param numeric-string $totalPrice
     */
    public function setTotalPrice(string $totalPrice): static
    {
        /* @var numeric-string $totalPrice */
        $this->totalPrice = $totalPrice;

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        $this->project = $project;

        return $this;
    }

    // Helper methods

    private function calculateTotalPrice(): void
    {
        $this->totalPrice = bcmul($this->quantity, $this->unitPrice, 2);
    }
}
