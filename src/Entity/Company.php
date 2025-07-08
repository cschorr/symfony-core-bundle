<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Traits\Set\SetCommunicationTrait;
use App\Entity\Traits\Single\StringNameExtensionTrait;
use App\Entity\Traits\Single\StringNameTrait;
use App\Repository\CompanyRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompanyRepository::class)]
#[ApiResource]
class Company extends AbstractEntity
{
    use StringNameTrait;
    use StringNameExtensionTrait;
    use SetCommunicationTrait;

    #[ORM\ManyToOne(inversedBy: 'companies')]
    private ?CompanyGroup $companyGroup = null;

    public function getCompanyGroup(): ?CompanyGroup
    {
        return $this->companyGroup;
    }

    public function setCompanyGroup(?CompanyGroup $companyGroup): static
    {
        $this->companyGroup = $companyGroup;

        return $this;
    }
}
