<?php

namespace App\Entity\Traits\Single;

use Doctrine\ORM\Mapping as ORM;

trait StringNameExtensionTrait
{
    #[ORM\Column(length: 255)]
    private string $nameExtension;

    public function getNameExtension(): string
    {
        return $this->nameExtension;
    }

    public function setNameExtension(string $nameExtension): static
    {
        $this->nameExtension = $nameExtension;

        return $this;
    }
}
