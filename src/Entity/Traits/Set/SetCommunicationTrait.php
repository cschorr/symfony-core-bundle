<?php

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

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    public function getCell(): ?string
    {
        return $this->cell;
    }

    public function setCell(?string $cell): void
    {
        $this->cell = $cell;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;
}
