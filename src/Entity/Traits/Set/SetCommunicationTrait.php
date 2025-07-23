<?php

declare(strict_types=1);

namespace App\Entity\Traits\Set;

use Doctrine\ORM\Mapping as ORM;

trait SetCommunicationTrait
{
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $cell = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $url = null;

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getCell(): ?string
    {
        return $this->cell;
    }

    public function setCell(?string $cell): static
    {
        $this->cell = $cell;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;
}
