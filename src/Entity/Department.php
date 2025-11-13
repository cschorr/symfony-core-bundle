<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\QueryParameter;
use C3net\CoreBundle\Entity\Traits\Set\SetCommunicationTrait;
use C3net\CoreBundle\Entity\Traits\Single\StringNameTrait;
use C3net\CoreBundle\Entity\Traits\Single\StringShortcodeTrait;
use C3net\CoreBundle\Repository\DepartmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DepartmentRepository::class)]
#[ApiResource(
    uriTemplate: '/companies/{companyId}/departments',
    uriVariables: [
        'companyId' => new Link(
            fromClass: Company::class,
            toProperty: 'company'
        ),
    ],
    operations: [
        new GetCollection(),
    ],
    paginationEnabled: true,
    paginationClientEnabled: true,
    paginationClientItemsPerPage: true,
    paginationItemsPerPage: 30,
    paginationMaximumItemsPerPage: 100
)]
#[ApiResource(
    paginationClientEnabled: true,
    paginationClientItemsPerPage: true,
    paginationEnabled: true,
    paginationItemsPerPage: 30,
    paginationMaximumItemsPerPage: 100,
    operations: [
        new Get(),
        new GetCollection(
            parameters: [
                'name' => new QueryParameter(
                    filter: SearchFilter::class . ':name:partial'
                ),
                'shortcode' => new QueryParameter(
                    filter: SearchFilter::class . ':shortcode:partial'
                ),
                'company' => new QueryParameter(
                    filter: SearchFilter::class . ':company'
                ),
                'contacts' => new QueryParameter(
                    filter: SearchFilter::class . ':contacts'
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
        'name' => 'ASC',
        'shortcode' => 'ASC',
    ],
)]
class Department extends AbstractEntity
{
    use StringNameTrait;
    use StringShortcodeTrait;
    use SetCommunicationTrait;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'departments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Company $company = null;

    #[ORM\ManyToOne(inversedBy: 'departments')]
    #[ORM\JoinColumn(nullable: true)]
    private ?CompanyGroup $companyGroup = null;

    /**
     * @var Collection<int, Contact>
     */
    #[ORM\OneToMany(targetEntity: Contact::class, mappedBy: 'department')]
    private Collection $contacts;

    public function __construct()
    {
        parent::__construct();
        $this->contacts = new ArrayCollection();
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->name ?? 'Unnamed Department';
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

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;

        return $this;
    }

    public function getCompanyGroup(): ?CompanyGroup
    {
        return $this->companyGroup;
    }

    public function setCompanyGroup(?CompanyGroup $companyGroup): static
    {
        $this->companyGroup = $companyGroup;

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
            $contact->setDepartment($this);
        }

        return $this;
    }

    public function removeContact(Contact $contact): static
    {
        if ($this->contacts->removeElement($contact)) {
            // Set the owning side to null (unless already changed)
            if ($contact->getDepartment() === $this) {
                $contact->setDepartment(null);
            }
        }

        return $this;
    }
}
