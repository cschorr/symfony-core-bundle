<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use C3net\CoreBundle\Entity\Traits\Set\CategorizableTrait;
use C3net\CoreBundle\Entity\Traits\Single\StringNameTrait;
use C3net\CoreBundle\Entity\Traits\Single\StringShortcodeTrait;
use C3net\CoreBundle\Enum\DomainEntityType;
use C3net\CoreBundle\Repository\CompanyGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompanyGroupRepository::class)]
#[ApiResource(
    operations: [
        new Get(uriTemplate: '/company-groups/{id}'),
        new GetCollection(uriTemplate: '/company-groups'),
        new Post(uriTemplate: '/company-groups'),
        new Put(uriTemplate: '/company-groups/{id}'),
        new Delete(uriTemplate: '/company-groups/{id}'),
    ],
)]
class CompanyGroup extends AbstractEntity
{
    use StringNameTrait;
    use StringShortcodeTrait;
    use CategorizableTrait;

    /**
     * @var Collection<int, Company>
     */
    #[ORM\OneToMany(targetEntity: Company::class, mappedBy: 'companyGroup')]
    private Collection $companies;

    /**
     * @var Collection<int, Department>
     */
    #[ORM\OneToMany(targetEntity: Department::class, mappedBy: 'companyGroup')]
    private Collection $departments;

    public function __construct()
    {
        parent::__construct();
        $this->companies = new ArrayCollection();
        $this->departments = new ArrayCollection();
    }

    /**
     * @return Collection<int, Company>
     */
    public function getCompanies(): Collection
    {
        return $this->companies;
    }

    public function addCompany(Company $company): static
    {
        if (!$this->companies->contains($company)) {
            $this->companies->add($company);
            $company->setCompanyGroup($this);
        }

        return $this;
    }

    public function removeCompany(Company $company): static
    {
        if ($this->companies->removeElement($company)) {
            // set the owning side to null (unless already changed)
            if ($company->getCompanyGroup() === $this) {
                $company->setCompanyGroup(null);
            }
        }

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
            $department->setCompanyGroup($this);
        }

        return $this;
    }

    public function removeDepartment(Department $department): static
    {
        if ($this->departments->removeElement($department)) {
            // set the owning side to null (unless already changed)
            if ($department->getCompanyGroup() === $this) {
                $department->setCompanyGroup(null);
            }
        }

        return $this;
    }

    #[\Override]
    public function __toString(): string
    {
        return !in_array($this->getName(), ['', '0'], true) ? $this->getName() : 'Unnamed Group';
    }

    protected function getCategorizableEntityType(): DomainEntityType
    {
        return DomainEntityType::CompanyGroup;
    }
}
