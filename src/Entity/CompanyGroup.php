<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Entity\Traits\Single\StringCodeTrait;
use App\Entity\Traits\Single\StringNameTrait;
use App\Repository\CompanyGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: CompanyGroupRepository::class)]
#[ApiResource(
    operations: [
        new Get(uriTemplate: '/company-groups/{id}'),
        new GetCollection(uriTemplate: '/company-groups'),
        new Post(uriTemplate: '/company-groups'),
        new Put(uriTemplate: '/company-groups/{id}'),
        new Delete(uriTemplate: '/company-groups/{id}'),
    ],
    normalizationContext: ['groups' => ['companyGroup:read']]
)]
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
    #[Groups(['companyGroup:read'])]
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
