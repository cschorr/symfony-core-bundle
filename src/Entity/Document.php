<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use C3net\CoreBundle\Entity\Traits\Set\CategorizableTrait;
use C3net\CoreBundle\Entity\Traits\Single\StringNameTrait;
use C3net\CoreBundle\Enum\DocumentType;
use C3net\CoreBundle\Enum\DomainEntityType;
use C3net\CoreBundle\Repository\DocumentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DocumentRepository::class)]
#[ApiResource(
    mercure: true,
    paginationClientEnabled: true,
    paginationClientItemsPerPage: true,
    paginationEnabled: true,
    paginationItemsPerPage: 30,
    paginationMaximumItemsPerPage: 100,
)]
#[ApiFilter(
    filterClass: SearchFilter::class,
    properties: [
        'transaction' => 'exact',
        'project' => 'exact',
        'documentType' => 'exact',
        'isPublic' => 'exact',
    ],
)]
class Document extends AbstractEntity
{
    use StringNameTrait;
    use CategorizableTrait;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: false, enumType: DocumentType::class)]
    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'enum' => ['contract', 'brief', 'deliverable', 'invoice', 'offer', 'signed_document', 'other'],
        ]
    )]
    #[Assert\NotBlank]
    private ?DocumentType $documentType = null;

    #[ORM\ManyToOne(inversedBy: 'documents')]
    private ?Transaction $transaction = null;

    #[ORM\ManyToOne]
    private ?Project $project = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    private ?string $fileName = null;

    #[ORM\Column(type: Types::STRING, length: 500)]
    #[Assert\NotBlank]
    private ?string $filePath = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $mimeType = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $fileSize = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false)]
    private bool $isPublic = false;

    #[ORM\ManyToOne]
    private ?User $uploadedBy = null;

    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    private int $version = 1;

    #[\Override]
    public function __toString(): string
    {
        return $this->fileName ?? $this->getName();
    }

    // Getters and Setters

    public function getDocumentType(): ?DocumentType
    {
        return $this->documentType;
    }

    public function setDocumentType(?DocumentType $documentType): static
    {
        $this->documentType = $documentType;

        return $this;
    }

    public function getTransaction(): ?Transaction
    {
        return $this->transaction;
    }

    public function setTransaction(?Transaction $transaction): static
    {
        $this->transaction = $transaction;

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        $this->project = $project;

        return $this;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(?string $fileName): static
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): static
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): static
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(?int $fileSize): static
    {
        $this->fileSize = $fileSize;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): static
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    public function getUploadedBy(): ?User
    {
        return $this->uploadedBy;
    }

    public function setUploadedBy(?User $uploadedBy): static
    {
        $this->uploadedBy = $uploadedBy;

        return $this;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): static
    {
        $this->version = $version;

        return $this;
    }

    // Helper methods

    public function getFileSizeFormatted(): string
    {
        if (null === $this->fileSize) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->fileSize;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            ++$unitIndex;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    protected function getCategorizableEntityType(): DomainEntityType
    {
        return DomainEntityType::Document;
    }
}
