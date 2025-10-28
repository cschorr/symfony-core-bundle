<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use C3net\CoreBundle\Entity\Traits\Set\CategorizableTrait;
use C3net\CoreBundle\Entity\Traits\Set\SetAddressTrait;
use C3net\CoreBundle\Entity\Traits\Set\SetCommunicationTrait;
use C3net\CoreBundle\Entity\Traits\Single\StringNameExtensionTrait;
use C3net\CoreBundle\Entity\Traits\Single\StringNameTrait;
use C3net\CoreBundle\Enum\DomainEntityType;
use C3net\CoreBundle\Repository\CompanyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompanyRepository::class)]
#[ApiResource(
    paginationClientEnabled: true,
    paginationClientItemsPerPage: true,
    paginationEnabled: true,
    paginationItemsPerPage: 30,
    paginationMaximumItemsPerPage: 100
)]
#[ApiFilter(
    filterClass: SearchFilter::class,
    properties: [
        'companyGroup' => 'partial',
        'employees' => 'partial',
        'departments' => 'exact',
        'name' => 'partial',
        'city' => 'partial',
    ]
)]
#[ApiFilter(
    filterClass: OrderFilter::class,
    properties: [
        'title' => 'ASC',
        'year' => 'DESC',
    ],
)]
class Company extends AbstractEntity
{
    use StringNameTrait;
    use StringNameExtensionTrait;
    use SetCommunicationTrait;
    use SetAddressTrait;
    use CategorizableTrait;

    #[ORM\ManyToOne(inversedBy: 'companies')]
    private ?CompanyGroup $companyGroup = null;

    /**
     * @var Collection<int, Contact>
     */
    #[ORM\OneToMany(targetEntity: Contact::class, mappedBy: 'company')]
    private Collection $employees;

    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'customer')]
    private Collection $transactions;

    /**
     * @var Collection<int, Department>
     */
    #[ORM\OneToMany(targetEntity: Department::class, mappedBy: 'company', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $departments;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true)]
    private ?string $imagePath = null;

    public function __construct()
    {
        parent::__construct();
        $this->employees = new ArrayCollection();
        $this->transactions = new ArrayCollection();
        $this->departments = new ArrayCollection();
    }

    #[\Override]
    public function __toString(): string
    {
        $name = trim($this->getName() ?? '');
        $nameExtension = trim($this->getNameExtension() ?? '');

        if ('' === $name || '0' === $name) {
            return 'Unnamed Company';
        }

        if ('' !== $nameExtension && '0' !== $nameExtension) {
            return $name . ' ' . $nameExtension;
        }

        return $name;
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
    public function getEmployees(): Collection
    {
        return $this->employees;
    }

    public function addEmployee(Contact $employee): static
    {
        if (!$this->employees->contains($employee)) {
            $this->employees->add($employee);
            $employee->setCompany($this);
        }

        return $this;
    }

    public function removeEmployee(Contact $employee): static
    {
        if ($this->employees->removeElement($employee)) {
            // set the owning side to null (unless already changed)
            if ($employee->getCompany() === $this) {
                $employee->setCompany(null);
            }
        }

        return $this;
    }

    protected function getCategorizableEntityType(): DomainEntityType
    {
        return DomainEntityType::Company;
    }

    public function getImagePath(): ?string
    {
        if (null === $this->imagePath) {
            return null;
        }

        $scheme = $_SERVER['REQUEST_SCHEME'] ?? 'https';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $scheme . '://' . $host . '/' . ltrim($this->imagePath, '/');
    }

    public function setImagePath(?string $imagePath): static
    {
        $this->imagePath = $imagePath;

        return $this;
    }

    /**
     * @return Collection<int, Department>
     */
    public function getDepartments(): Collection
    {
        return $this->departments;
    }

    public function addDepartment(Department $department): static
    {
        if (!$this->departments->contains($department)) {
            $this->departments->add($department);
            $department->setCompany($this);
        }

        return $this;
    }

    public function removeDepartment(Department $department): static
    {
        if ($this->departments->removeElement($department)) {
            // Set the owning side to null (unless already changed)
            if ($department->getCompany() === $this) {
                $department->setCompany(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): static
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions->add($transaction);
            $transaction->setCustomer($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): static
    {
        if ($this->transactions->removeElement($transaction)) {
            if ($transaction->getCustomer() === $this) {
                $transaction->setCustomer(null);
            }
        }

        return $this;
    }
}
