<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\QueryParameter;
use C3net\CoreBundle\Entity\Traits\Set\CategorizableTrait;
use C3net\CoreBundle\Entity\Traits\Set\SetAddressTrait;
use C3net\CoreBundle\Entity\Traits\Set\SetCommunicationTrait;
use C3net\CoreBundle\Entity\Traits\Set\SetNamePersonTrait;
use C3net\CoreBundle\Entity\Traits\Tree\NestedTreeTrait;
use C3net\CoreBundle\Enum\DomainEntityType;
use C3net\CoreBundle\Enum\Gender;
use C3net\CoreBundle\Repository\ContactRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[Gedmo\Tree(type: 'nested')]
#[ORM\Table(name: 'contacts')]
#[ORM\Entity(repositoryClass: ContactRepository::class)]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap([
    'contact' => Contact::class,
])]
#[ApiResource(
    uriTemplate: '/companies/{companyId}/employees',
    uriVariables: [
        'companyId' => new Link(
            fromClass: Company::class,
            toProperty: 'company'
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
    uriTemplate: '/departments/{departmentId}/contacts',
    uriVariables: [
        'departmentId' => new Link(
            fromClass: Department::class,
            toProperty: 'department'
        ),
    ],
    operations: [
        new GetCollection(),
    ],
    extraProperties: ['openapi_context' => ['tags' => ['Department']]],
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
                'company' => new QueryParameter(
                    filter: SearchFilter::class . ':company'
                ),
                'department' => new QueryParameter(
                    filter: SearchFilter::class . ':department'
                ),
                'standin' => new QueryParameter(
                    filter: SearchFilter::class . ':standin'
                ),
                'gender' => new QueryParameter(
                    filter: SearchFilter::class . ':gender'
                ),
            ]
        ),
        new Post(),
        new Put(),
        new Patch(),
        new Delete(),
    ]
)]
class Contact extends AbstractEntity
{
    use SetNamePersonTrait;
    use SetCommunicationTrait;
    use SetAddressTrait;
    use NestedTreeTrait;
    use CategorizableTrait;

    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'employees')]
    private ?Company $company = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $position = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 32, nullable: true, enumType: Gender::class)]
    private ?Gender $gender = null;

    #[ORM\ManyToOne(inversedBy: 'contacts')]
    private ?Department $department = null;

    #[ORM\ManyToOne(targetEntity: Contact::class)]
    private ?Contact $standin = null;

    /**
     * @var Collection<int, Project>
     */
    #[ORM\ManyToMany(targetEntity: Project::class, mappedBy: 'contact')]
    private Collection $projects;

    public function __construct()
    {
        parent::__construct();
        $this->initializeTreeCollections();
        $this->projects = new ArrayCollection();
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->getFullName() ?? 'Unnamed Contact';
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

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(?string $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getGender(): ?Gender
    {
        return $this->gender;
    }

    public function setGender(?Gender $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    public function getDepartment(): ?Department
    {
        return $this->department;
    }

    public function setDepartment(?Department $department): static
    {
        $this->department = $department;

        return $this;
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
            $project->addContact($this);
        }

        return $this;
    }

    public function removeProject(Project $project): static
    {
        if ($this->projects->removeElement($project)) {
            $project->removeContact($this);
        }

        return $this;
    }

    public function getStandin(): ?Contact
    {
        return $this->standin;
    }

    public function setStandin(?Contact $standin): static
    {
        $this->standin = $standin;

        return $this;
    }

    protected function getCategorizableEntityType(): DomainEntityType
    {
        return DomainEntityType::Contact;
    }
}
