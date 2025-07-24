<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\Set\SetNamePersonTrait;
use App\Entity\Traits\Set\SetAddressTrait;
use App\Entity\Traits\Set\SetCommunicationTrait;
use ApiPlatform\Metadata\ApiResource;
use App\Repository\ContactRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContactRepository::class)]
#[ApiResource]
class Contact extends AbstractEntity
{
    use SetNamePersonTrait;
    use SetCommunicationTrait;
    use SetAddressTrait;

    #[\Override]
    public function __toString(): string
    {
        return $this->getId() ?? 'Unnamed Contact';
    }
}
