<?php

namespace App\Entity\Traits\Single;

use Doctrine\ORM\Mapping as ORM;

trait BoolActiveTrait
{
    #[ORM\Column(type: 'boolean')]
    private bool $active = true; # TODO: default true?

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }
}
