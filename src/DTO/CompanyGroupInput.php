<?php

declare(strict_types=1);

namespace C3net\CoreBundle\DTO;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Input DTO for CompanyGroup write operations.
 * Allows flexible relationship formats (IRIs or plain UUIDs).
 */
final class CompanyGroupInput
{
    #[Assert\NotBlank(groups: ['create'])]
    #[Assert\Length(max: 255)]
    public ?string $name = null;

    #[Assert\Length(max: 50)]
    public ?string $shortcode = null;

    public ?string $notes = null;

    public bool $active = true;

    /**
     * @var array<int, string>|null Array of company IRIs or UUIDs
     */
    #[SerializedName('companies')]
    public ?array $companyIds = null;

    /**
     * @var array<int, string>|null Array of department IRIs or UUIDs
     */
    #[SerializedName('departments')]
    public ?array $departmentIds = null;

    /**
     * @var array<int, string>|null Array of category IRIs or UUIDs
     */
    #[SerializedName('categories')]
    public ?array $categoryIds = null;
}
