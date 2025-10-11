<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use C3net\CoreBundle\Entity\Traits\Single\StringNameTrait;
use C3net\CoreBundle\Entity\Traits\Single\StringShortcodeTrait;
use C3net\CoreBundle\Repository\DepartmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DepartmentRepository::class)]
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
        'name' => 'partial',
        'shortcode' => 'partial',
        'company' => 'exact',
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

    #[ORM\ManyToOne(inversedBy: 'departments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Company $company = null;

    public function __construct()
    {
        parent::__construct();
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->name ?? 'Unnamed Department';
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
}
