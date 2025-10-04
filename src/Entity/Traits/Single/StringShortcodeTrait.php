<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Entity\Traits\Single;

use Doctrine\ORM\Mapping as ORM;

trait StringShortcodeTrait
{
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $shortcode = null;

    public function getShortcode(): ?string
    {
        return $this->shortcode;
    }

    public function setShortcode(?string $shortcode): static
    {
        $this->shortcode = $shortcode;

        return $this;
    }
}
