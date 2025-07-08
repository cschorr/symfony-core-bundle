<?php

namespace App\Entity;

use App\Entity\Traits\Set\BlameableEntity;
use App\Entity\Traits\Single\BoolActiveTrait;
use App\Entity\Traits\Single\UuidTrait;
use App\Entity\Traits\Single\StringNotesTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\MappedSuperclass]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt')]
class AbstractEntity implements \Stringable
{
    use UuidTrait;
    use BoolActiveTrait;

    use BlameableEntity;
    use SoftDeleteableEntity;
    use TimestampableEntity;

    use StringNotesTrait;

    ##[Gedmo\SortablePosition]
    ##[ORM\Column(name: 'position', type: 'integer')]
    #private int $sorting;

    public function __toString(): string
    {
        return '';
    }

    /*
    public function getSorting(): int
    {
        return $this->sorting;
    }

    public function setSorting(int $sorting): static
    {
        $this->sorting = $sorting;

        return $this;
    }
    */
}
