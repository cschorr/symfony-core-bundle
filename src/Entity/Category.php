<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[Gedmo\Tree(type: 'nested')]
#[ORM\Table(name: 'categories')]
#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ApiResource(
    shortName: 'Category',
    description: 'Nested categories.',
)]
class Category extends AbstractEntity
{
    #[Gedmo\TreeLeft]
    #[ORM\Column(name: 'lft', type: Types::INTEGER)]
    private ?int $lft;

    #[Gedmo\TreeLevel]
    #[ORM\Column(name: 'lvl', type: Types::INTEGER)]
    private ?int $lvl;

    #[Gedmo\TreeRight]
    #[ORM\Column(name: 'rgt', type: Types::INTEGER)]
    private ?int $rgt;

    #[Gedmo\TreeRoot]
    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(name: 'tree_root', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Category $root;

    #[Gedmo\TreeParent]
    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Category $parent;

    #[ORM\OneToMany(targetEntity: Category::class, mappedBy: 'parent')]
    #[ORM\OrderBy(['lft' => 'ASC'])]
    private Collection $children;

    public function __construct()
    {
        parent::__construct();
        $this->children = new ArrayCollection();
    }

    public function getRoot(): ?self
    {
        return $this->root;
    }

    public function setParent(?self $parent = null): void
    {
        $this->parent = $parent;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function setChildren(Collection $children): void
    {
        $this->children = $children;
    }
}
