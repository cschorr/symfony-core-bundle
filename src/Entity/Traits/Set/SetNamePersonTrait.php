<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Entity\Traits\Set;

use Doctrine\ORM\Mapping as ORM;

trait SetNamePersonTrait
{
    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $academicTitle = null;

    // helper methods for name variants
    public function getFullName(): ?string
    {
        if ($this->academicTitle) {
            return $this->academicTitle . ' ' . $this->firstName . ' ' . $this->lastName;
        }

        return $this->firstName . ' ' . $this->lastName;
    }

    public function getName(): ?string
    {
        if ($this->academicTitle) {
            return $this->academicTitle . ' ' . $this->firstName . ' ' . $this->lastName;
        }

        return $this->firstName . ' ' . $this->lastName;
    }

    public function getFormalFullName(): ?string
    {
        if ($this->academicTitle) {
            return $this->academicTitle . ' ' . $this->lastName . ', ' . $this->firstName;
        }

        return $this->lastName . ', ' . $this->firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

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
