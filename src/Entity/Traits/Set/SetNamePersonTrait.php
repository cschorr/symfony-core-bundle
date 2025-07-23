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
    private ?string $nameBirth = null;

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

    public function getNameBirth(): ?string
    {
        return $this->nameBirth;
    }

    public function setNameBirth(?string $nameBirth): static
    {
        $this->nameBirth = $nameBirth;

        return $this;
    }
}
