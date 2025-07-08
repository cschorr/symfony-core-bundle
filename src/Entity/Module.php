<?php

namespace App\Entity;

use App\Entity\Traits\Single\StringNameTrait;
use App\Repository\AddressRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AddressRepository::class)]
class Address extends AbstractEntity
{
    use StringNameTrait;
}
