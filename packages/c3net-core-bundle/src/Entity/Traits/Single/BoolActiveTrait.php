<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Entity\Traits\Single;

use Doctrine\ORM\Mapping as ORM;

trait BoolActiveTrait
{
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    private bool $active = true; // TODO: default true?

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }
}
