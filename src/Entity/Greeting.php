<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Traits\Single\StringNameTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(mercure: true)]
#[ORM\Entity]
class Greeting extends AbstractEntity
{
    use StringNameTrait;

    public function __toString()
    {
        return $this->name;
    }
}
