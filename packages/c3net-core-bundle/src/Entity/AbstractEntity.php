<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Entity;

use C3net\CoreBundle\Entity\Traits\Set\BlameableEntity;
use C3net\CoreBundle\Entity\Traits\Single\BoolActiveTrait;
use C3net\CoreBundle\Entity\Traits\Single\StringNotesTrait;
use C3net\CoreBundle\Entity\Traits\Single\UuidTrait;
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
