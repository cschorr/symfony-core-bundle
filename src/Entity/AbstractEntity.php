<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\Set\BlameableEntity;
use App\Entity\Traits\Single\BoolActiveTrait;
use App\Entity\Traits\Single\StringNotesTrait;
use App\Entity\Traits\Single\UuidTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;

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

    public function __construct()
    {
        // Set default timestamps since Gedmo might not be fully configured
        if (!$this->createdAt) {
            $this->createdAt = new \DateTime();
        }

        if (!$this->updatedAt) {
            $this->updatedAt = new \DateTime();
        }
    }

    public function __toString(): string
    {
        return '';
    }
}
