<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\Single\StringCodeTrait;
use App\Entity\Traits\Single\StringNameTrait;
use App\Repository\CompanyGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompanyGroupRepository::class)]
class CompanyGroup extends AbstractEntity
{
    use StringNameTrait;
    use StringCodeTrait;

    /**
     * @var Collection<int, Company>
     */
    #[ORM\OneToMany(targetEntity: Company::class, mappedBy: 'companyGroup')]
    private Collection $companies;

    public function __construct()
    {
        parent::__construct();
        $this->companies = new ArrayCollection();
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

    #[\Override]
    public function __toString(): string
    {
        return $this->getName() ?? 'Unnamed Group';
    }
}
