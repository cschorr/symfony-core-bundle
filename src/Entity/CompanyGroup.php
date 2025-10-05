<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use C3net\CoreBundle\Entity\Traits\Single\StringNameTrait;
use C3net\CoreBundle\Entity\Traits\Single\StringShortcodeTrait;
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
        return $this->getName() ?: 'Unnamed Group';
    }
}
