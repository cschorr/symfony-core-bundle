<?php

declare(strict_types=1);

namespace App\Entity\Traits\Set;

use Doctrine\ORM\Mapping as ORM;

trait SetNamePersonTrait
{
    #[ORM\Column(length: 255)]
    private ?string $nameLast = null;

    #[ORM\Column(length: 255)]
    private ?string $nameFirst = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $academicTitle = null;

    // helper methods for name variants
    public function getFullName(): ?string
    {
        if ($this->academicTitle) {
            return $this->academicTitle . ' ' . $this->nameFirst . ' ' . $this->nameLast;
        }
        return $this->nameFirst . ' ' . $this->nameLast;
    }

    public function getFormalFullName(): ?string
    {
        if ($this->academicTitle) {
            return $this->academicTitle . ' ' . $this->nameLast . ', ' . $this->nameFirst;
        }
        return $this->nameLast . ', ' . $this->nameFirst;
    }

    public function getNameLast(): ?string
    {
        return $this->nameLast;
    }

    public function setNameLast(string $nameLast): static
    {
        $this->nameLast = $nameLast;

        return $this;
    }

    public function getNameFirst(): ?string
    {
        return $this->nameFirst;
    }

    public function setNameFirst(string $nameFirst): static
    {
        $this->nameFirst = $nameFirst;

        return $this;
    }

    public function getAcademicTitle(): ?string
    {
        return $this->academicTitle;
    }

    public function setAcademicTitle(?string $academicTitle): static
    {
        $this->academicTitle = $academicTitle;

        return $this;
    }
}
