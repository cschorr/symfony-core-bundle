<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use App\Entity\VideoProject;
use C3net\CoreBundle\Entity\Traits\Set\CategorizableTrait;
use C3net\CoreBundle\Entity\Traits\Set\SetAddressTrait;
use C3net\CoreBundle\Entity\Traits\Set\SetCommunicationTrait;
use C3net\CoreBundle\Entity\Traits\Set\SetNamePersonTrait;
use C3net\CoreBundle\Entity\Traits\Tree\NestedTreeTrait;
use C3net\CoreBundle\Enum\DomainEntityType;
use C3net\CoreBundle\Repository\ContactRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[Gedmo\Tree(type: 'nested')]
#[ORM\Table(name: 'contacts')]
#[ORM\Entity(repositoryClass: ContactRepository::class)]
#[ApiResource(
    paginationClientEnabled: true,
    paginationClientItemsPerPage: true,
    paginationEnabled: true,
    paginationItemsPerPage: 30,
    paginationMaximumItemsPerPage: 100
)]
#[ApiFilter(
    filterClass: OrderFilter::class,
    properties: [
        'title' => 'ASC',
        'year' => 'DESC',
    ],
)]
#[ApiFilter(
    filterClass: SearchFilter::class,
    properties: [
        'company' => 'exact',
        'department' => 'exact',
        'standin' => 'exact',
    ],
)]
class Contact extends AbstractEntity
{
    use SetNamePersonTrait;
    use SetCommunicationTrait;
    use SetAddressTrait;
    use NestedTreeTrait;
    use CategorizableTrait;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    private ?Company $company = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $position = null;

    #[ORM\ManyToOne(inversedBy: 'contacts')]
    private ?Department $department = null;

    #[ORM\ManyToOne(targetEntity: Contact::class)]
    private ?Contact $standin = null;

    /**
     * @var Collection<int, Project>
     */
    #[ORM\ManyToMany(targetEntity: Project::class, mappedBy: 'contact')]
    private Collection $projects;

    /**
     * @var Collection<int, VideoProject>
     */
    #[ORM\OneToMany(targetEntity: VideoProject::class, mappedBy: 'responsiblePerson')]
    private Collection $videoProjects;

    public function __construct()
    {
        parent::__construct();
        $this->initializeTreeCollections();
        $this->projects = new ArrayCollection();
        $this->videoProjects = new ArrayCollection();
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

    /**
     * @return Collection<int, VideoProject>
     */
    public function getVideoProjects(): Collection
    {
        return $this->videoProjects;
    }

    public function addVideoProject(VideoProject $videoProject): static
    {
        if (!$this->videoProjects->contains($videoProject)) {
            $this->videoProjects->add($videoProject);
            $videoProject->setResponsiblePerson($this);
        }

        return $this;
    }

    public function removeVideoProject(VideoProject $videoProject): static
    {
        if ($this->videoProjects->removeElement($videoProject)) {
            // set the owning side to null (unless already changed)
            if ($videoProject->getResponsiblePerson() === $this) {
                $videoProject->setResponsiblePerson(null);
            }
        }

        return $this;
    }
}
