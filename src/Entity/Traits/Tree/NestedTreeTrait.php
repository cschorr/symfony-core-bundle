<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Entity\Traits\Tree;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Trait for implementing Gedmo Nested Tree behavior
 *
 * This trait provides all necessary properties and methods for entities
 * that need to be organized in a nested tree structure using the
 * Doctrine Extensions Tree behavior.
 *
 * @see https://github.com/doctrine-extensions/DoctrineExtensions/blob/main/doc/tree.md
 */
trait NestedTreeTrait
{
    #[Gedmo\TreeLeft]
    #[ORM\Column(name: 'lft', type: Types::INTEGER)]
    private ?int $lft = null;

    #[Gedmo\TreeLevel]
    #[ORM\Column(name: 'lvl', type: Types::INTEGER)]
    private ?int $lvl = null;

    #[Gedmo\TreeRight]
    #[ORM\Column(name: 'rgt', type: Types::INTEGER)]
    private ?int $rgt = null;

    #[Gedmo\TreeRoot]
    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'tree_root', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?self $root = null;

    #[Gedmo\TreeParent]
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?self $parent = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    #[ORM\OrderBy(['lft' => 'ASC'])]
    private Collection $children;

    /**
     * Initialize the children collection
     * This should be called in the entity's constructor
     */
    protected function initializeTreeCollections(): void
    {
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

    /**
     * @return Collection<int, self>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    /**
     * @param Collection<int, self> $children
     */
    public function setChildren(Collection $children): void
    {
        $this->children = $children;
    }

    public function getLft(): ?int
    {
        return $this->lft;
    }

    public function setLft(?int $lft): void
    {
        $this->lft = $lft;
    }

    public function getLvl(): ?int
    {
        return $this->lvl;
    }

    public function setLvl(?int $lvl): void
    {
        $this->lvl = $lvl;
    }

    public function getRgt(): ?int
    {
        return $this->rgt;
    }

    public function setRgt(?int $rgt): void
    {
        $this->rgt = $rgt;
    }

    /**
     * Check if this node is a root node
     */
    public function isRoot(): bool
    {
        return $this->parent === null;
    }

    /**
     * Check if this node is a leaf node (has no children)
     */
    public function isLeaf(): bool
    {
        return $this->children->isEmpty();
    }

    /**
     * Get the depth level of this node in the tree
     */
    public function getLevel(): ?int
    {
        return $this->lvl;
    }
}