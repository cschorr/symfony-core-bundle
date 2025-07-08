<?php

namespace App\Entity;

use App\Entity\Traits\Single\StringNameTrait;
use App\Entity\Traits\Single\StringTextTrait;
use App\Repository\ModuleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ModuleRepository::class)]
class Module extends AbstractEntity
{
    use StringNameTrait;
    use StringTextTrait;
}
