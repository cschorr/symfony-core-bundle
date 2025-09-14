<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Entity\Traits\Single;

use Doctrine\ORM\Mapping as ORM;

trait StringCodeTrait
{
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $code = null;

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }
}
