<?php

namespace App\Entity;

use App\Entity\Traits\Single\BoolActiveTrait;
use App\Entity\Traits\Single\UuidTrait;
use App\Entity\Traits\Single\StringNotesTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
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

    public function __toString()
    {
        return '';
    }
}
