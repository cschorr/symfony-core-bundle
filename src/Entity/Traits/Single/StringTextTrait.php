<?php

declare(strict_types=1);

namespace App\Entity\Traits\Single;

use Doctrine\ORM\Mapping as ORM;

trait StringTextTrait
{
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    private ?string $text = null;

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): static
    {
        $this->text = $text;

        return $this;
    }
}
