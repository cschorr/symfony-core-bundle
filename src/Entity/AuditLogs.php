<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use C3net\CoreBundle\Repository\AuditLogsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditLogsRepository::class)]
#[ApiResource(
    operations: [
        new Get(uriTemplate: '/audit-logs/{id}'),
        new GetCollection(uriTemplate: '/audit-logs'),
        new GetCollection(
            uriTemplate: '/audit-logs/authors',
            provider: \C3net\CoreBundle\State\AuditLogAuthorsProvider::class,
            name: 'audit_logs_get_authors',
        ),
        new GetCollection(
            uriTemplate: '/audit-logs/resources',
            provider: \C3net\CoreBundle\State\AuditLogResourcesProvider::class,
            name: 'audit_logs_get_resources',
        ),
        new GetCollection(
            uriTemplate: '/audit-logs/actions',
            provider: \C3net\CoreBundle\State\AuditLogActionsProvider::class,
            name: 'audit_logs_get_actions',
        ),
        new GetCollection(
            uriTemplate: '/audit-logs/filters',
            provider: \C3net\CoreBundle\State\AuditLogFiltersProvider::class,
            name: 'audit_logs_get_filters',
        ),
        new Post(uriTemplate: '/audit-logs'),
        new Put(uriTemplate: '/audit-logs/{id}'),
        new Delete(uriTemplate: '/audit-logs/{id}'),
    ],
    paginationClientEnabled: true,
    paginationClientItemsPerPage: true,
    paginationEnabled: true,
    paginationItemsPerPage: 30,
    paginationMaximumItemsPerPage: 100,
)]
#[ApiFilter(
    filterClass: SearchFilter::class,
    properties: [
        'author' => 'exact',
        'resource' => 'partial',
        'action' => 'exact',
    ],
)]
#[ApiFilter(
    filterClass: DateFilter::class,
    properties: [
        'createdAt',
    ],
)]
#[ApiFilter(
    filterClass: OrderFilter::class,
    properties: [
        'createdAt' => 'DESC',
        'resource' => 'ASC',
        'action' => 'ASC',
    ],
)]
class AuditLogs extends AbstractEntity
{
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $resource = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    private ?string $meta = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $action = null;

    #[ORM\ManyToOne(inversedBy: 'auditLogs')]
    private ?User $author = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    private ?string $data = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    private ?string $previousData = null;

    public function getResource(): ?string
    {
        return $this->resource;
    }

    public function setResource(?string $resource): static
    {
        $this->resource = $resource;

        return $this;
    }

    public function getMeta(): ?string
    {
        return $this->meta;
    }

    public function setMeta(?string $meta): static
    {
        $this->meta = $meta;

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(?string $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getData(): ?string
    {
        return $this->data;
    }

    public function setData(?string $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function getPreviousData(): ?string
    {
        return $this->previousData;
    }

    public function setPreviousData(?string $previousData): static
    {
        $this->previousData = $previousData;

        return $this;
    }
}
