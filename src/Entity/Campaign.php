<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use C3net\CoreBundle\Entity\Traits\Set\CategorizableTrait;
use C3net\CoreBundle\Entity\Traits\Set\SetStartEndTrait;
use C3net\CoreBundle\Entity\Traits\Single\StringNameTrait;
use C3net\CoreBundle\Entity\Traits\Single\StringShortcodeTrait;
use C3net\CoreBundle\Enum\DomainEntityType;
use C3net\CoreBundle\Repository\CampaignRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CampaignRepository::class)]
#[ApiResource(
    uriTemplate: '/companies/{companyId}/campaigns',
    uriVariables: [
        'companyId' => new Link(
            fromClass: Company::class,
            toProperty: 'customer'
        ),
    ],
    operations: [
        new GetCollection(),
    ],
    extraProperties: ['openapi_context' => ['tags' => ['Company']]],
    paginationEnabled: true,
    paginationClientEnabled: true,
    paginationClientItemsPerPage: true,
    paginationItemsPerPage: 30,
    paginationMaximumItemsPerPage: 100
)]
#[ApiResource(
    uriTemplate: '/transactions/{transactionId}/campaigns',
    uriVariables: [
        'transactionId' => new Link(
            fromClass: Transaction::class,
            toProperty: 'transaction'
        ),
    ],
    operations: [
        new GetCollection(),
    ],
    extraProperties: ['openapi_context' => ['tags' => ['Transaction']]],
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
        new GetCollection(),
        new Post(),
        new Put(),
        new Patch(),
        new Delete(),
    ]
)]
class Campaign extends AbstractEntity
{
    use StringNameTrait;
    use StringShortcodeTrait;
    use SetStartEndTrait;
    use CategorizableTrait;

    /**
     * @var Collection<int, Project>
     */
    #[ORM\OneToMany(targetEntity: Project::class, mappedBy: 'campaign')]
    private Collection $projects;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'campaigns')]
    private ?Transaction $transaction = null;

    public function __construct()
    {
        $this->projects = new ArrayCollection();
        parent::__construct();
    }

    /**
     * @return Collection<int, Project>
     */
    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function addProject(Project $project): static
    {
        if (!$this->projects->contains($project)) {
            $this->projects->add($project);
            $project->setCampaign($this);
        }

        return $this;
    }

    public function removeProject(Project $project): static
    {
        if ($this->projects->removeElement($project)) {
            // set the owning side to null (unless already changed)
            if ($project->getCampaign() === $this) {
                $project->setCampaign(null);
            }
        }

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

    protected function getCategorizableEntityType(): DomainEntityType
    {
        return DomainEntityType::Campaign;
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

    /**
     * Get the customer company from the associated transaction.
     * This is a convenience method that accesses the customer through the transaction relationship.
     *
     * @return Company|null The customer company, or null if no transaction is associated
     */
    public function getCustomer(): ?Company
    {
        return $this->transaction?->getCustomer();
    }
}
