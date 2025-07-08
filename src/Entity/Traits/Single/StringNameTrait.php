<?php

namespace App\Entity\Traits\Single;

use Doctrine\ORM\Mapping as ORM;

trait StringNameTrait
{
    #[ORM\Column(length: 255)]
    private string $name;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
