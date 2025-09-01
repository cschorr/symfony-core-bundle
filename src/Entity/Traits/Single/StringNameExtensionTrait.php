<?php

declare(strict_types=1);

namespace App\Entity\Traits\Single;

use Doctrine\ORM\Mapping as ORM;

trait StringNameExtensionTrait
{
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nameExtension = null;

    public function getNameExtension(): ?string
    {
        return $this->nameExtension;
    }

    public function setNameExtension(?string $nameExtension): static
    {
        $this->nameExtension = $nameExtension;

        return $this;
    }
}
