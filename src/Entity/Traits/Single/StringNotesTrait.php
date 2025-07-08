<?php

namespace App\Entity\Traits\Single;

use Doctrine\ORM\Mapping as ORM;

trait StringNotesTrait
{
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }
}
