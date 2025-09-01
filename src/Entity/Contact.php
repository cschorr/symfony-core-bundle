<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use App\Entity\Traits\Set\SetNamePersonTrait;
use App\Entity\Traits\Set\SetAddressTrait;
use App\Entity\Traits\Set\SetCommunicationTrait;
use ApiPlatform\Metadata\ApiResource;
use App\Repository\ContactRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContactRepository::class)]
#[ApiResource(
    paginationClientEnabled: true,
    paginationClientItemsPerPage: true,
    paginationEnabled: true,
    paginationItemsPerPage: 30,
    paginationMaximumItemsPerPage: 100
)]
#[ApiFilter(
    filterClass: OrderFilter::class,
    properties: [
        'title' => 'ASC',
        'year' => 'DESC',
    ],
)]
class Contact extends AbstractEntity
{
    use SetNamePersonTrait;
    use SetCommunicationTrait;
    use SetAddressTrait;

    #[\Override]
    public function __toString(): string
    {
        return $this->getFullName() ?? 'Unnamed Contact';
    }
}
