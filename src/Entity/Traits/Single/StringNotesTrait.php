<?php

declare(strict_types=1);

namespace App\Entity\Traits\Single;

use Doctrine\ORM\Mapping as ORM;

trait StringNotesTrait
{
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    private ?string $notes = null;

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }
}
