<?php

declare(strict_types=1);

namespace C3net\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use C3net\CoreBundle\DTO\CompanyGroupInput;
use C3net\CoreBundle\Entity\Category;
use C3net\CoreBundle\Entity\Company;
use C3net\CoreBundle\Entity\CompanyGroup;
use C3net\CoreBundle\Entity\Department;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Uid\Uuid;

/**
 * Custom processor for CompanyGroup write operations.
 * Handles bidirectional relationship synchronization for companies and departments.
 * Works with CompanyGroupInput DTO to accept flexible relationship formats.
 */
final readonly class CompanyGroupWriteProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param CompanyGroupInput $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof CompanyGroupInput) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        // Get the existing entity for PUT or create new for POST
        $companyGroup = $context['previous_data'] ?? null;

        if (!$companyGroup instanceof CompanyGroup) {
            $companyGroup = new CompanyGroup();
        }

        // Map DTO fields to entity (only if provided)
        if ($data->name !== null) {
            $companyGroup->setName($data->name);
        }

        if ($data->shortcode !== null) {
            $companyGroup->setShortcode($data->shortcode);
        }

        $companyGroup->setNotes($data->notes);
        $companyGroup->setActive($data->active);

        // Sync companies if provided
        if ($data->companyIds !== null) {
            $this->syncCompanies($companyGroup, $data->companyIds);
        }

        // Sync departments if provided
        if ($data->departmentIds !== null) {
            $this->syncDepartments($companyGroup, $data->departmentIds);
        }

        // Sync categories if provided
        if ($data->categoryIds !== null) {
            $this->syncCategories($companyGroup, $data->categoryIds);
        }

        // Persist and flush
        $this->entityManager->persist($companyGroup);
        $this->entityManager->flush();

        return $companyGroup;
    }

    /**
     * @param array<int, string> $companyIris Array of company IRIs or UUIDs
     */
    private function syncCompanies(CompanyGroup $companyGroup, array $companyIris): void
    {
        // Get current companies
        $currentCompanies = $companyGroup->getCompanies()->toArray();
        $newCompanyIds = $this->extractIdsFromIris($companyIris);

        // Find companies to add and remove
        $companiesToAdd = [];
        $companiesToRemove = [];

        // Fetch all new companies using string UUIDs
        if (!empty($newCompanyIds)) {
            // Fetch companies one by one to ensure UUID conversion works
            foreach ($newCompanyIds as $companyId) {
                $company = $this->entityManager->getRepository(Company::class)
                    ->find(Uuid::fromString($companyId));
                if ($company !== null) {
                    $companiesToAdd[] = $company;
                }
            }
        }

        // Validate that companies are not already assigned to other groups
        // (excluding companies already in the current group)
        $alreadyAssigned = [];
        foreach ($companiesToAdd as $company) {
            $existingGroup = $company->getCompanyGroup();
            // Only error if company is in a DIFFERENT group (not the one we're updating)
            if ($existingGroup !== null &&
                $existingGroup->getId() !== null &&
                $companyGroup->getId() !== null &&
                (string) $existingGroup->getId() !== (string) $companyGroup->getId()) {
                $alreadyAssigned[] = sprintf(
                    '%s (already in "%s")',
                    $company->getName(),
                    $existingGroup->getName()
                );
            }
        }

        if (!empty($alreadyAssigned)) {
            throw new UnprocessableEntityHttpException(
                'The following companies are already assigned to other company groups: ' .
                implode(', ', $alreadyAssigned)
            );
        }

        // Determine which companies to remove (current companies not in the new list)
        foreach ($currentCompanies as $company) {
            if (!\in_array((string) $company->getId(), $newCompanyIds, true)) {
                $companiesToRemove[] = $company;
            }
        }

        // Remove old companies
        foreach ($companiesToRemove as $company) {
            $companyGroup->removeCompany($company);
        }

        // Add new companies
        foreach ($companiesToAdd as $company) {
            $companyGroup->addCompany($company);
        }
    }

    /**
     * @param array<int, string> $departmentIris Array of department IRIs or UUIDs
     */
    private function syncDepartments(CompanyGroup $companyGroup, array $departmentIris): void
    {
        // Get current departments
        $currentDepartments = $companyGroup->getDepartments()->toArray();
        $newDepartmentIds = $this->extractIdsFromIris($departmentIris);

        // Find departments to add and remove
        $departmentsToAdd = [];
        $departmentsToRemove = [];

        // Fetch all new departments using string UUIDs
        if (!empty($newDepartmentIds)) {
            // Fetch departments one by one to ensure UUID conversion works
            foreach ($newDepartmentIds as $departmentId) {
                $department = $this->entityManager->getRepository(Department::class)
                    ->find(Uuid::fromString($departmentId));
                if ($department !== null) {
                    $departmentsToAdd[] = $department;
                }
            }
        }

        // Validate that departments are not already assigned to other groups
        // (excluding departments already in the current group)
        $alreadyAssigned = [];
        foreach ($departmentsToAdd as $department) {
            $existingGroup = $department->getCompanyGroup();
            // Only error if department is in a DIFFERENT group (not the one we're updating)
            if ($existingGroup !== null &&
                $existingGroup->getId() !== null &&
                $companyGroup->getId() !== null &&
                (string) $existingGroup->getId() !== (string) $companyGroup->getId()) {
                $alreadyAssigned[] = sprintf(
                    '%s (already in "%s")',
                    $department->getName(),
                    $existingGroup->getName()
                );
            }
        }

        if (!empty($alreadyAssigned)) {
            throw new UnprocessableEntityHttpException(
                'The following departments are already assigned to other company groups: ' .
                implode(', ', $alreadyAssigned)
            );
        }

        // Determine which departments to remove (current departments not in the new list)
        foreach ($currentDepartments as $department) {
            if (!\in_array((string) $department->getId(), $newDepartmentIds, true)) {
                $departmentsToRemove[] = $department;
            }
        }

        // Remove old departments
        foreach ($departmentsToRemove as $department) {
            $companyGroup->removeDepartment($department);
        }

        // Add new departments
        foreach ($departmentsToAdd as $department) {
            $companyGroup->addDepartment($department);
        }
    }

    /**
     * @param array<int, string> $categoryIris Array of category IRIs or UUIDs
     */
    private function syncCategories(CompanyGroup $companyGroup, array $categoryIris): void
    {
        // Clear existing categories
        $companyGroup->getCategories()->clear();

        // Extract IDs and fetch categories
        $categoryIds = $this->extractIdsFromIris($categoryIris);
        if (empty($categoryIds)) {
            return;
        }

        // Fetch categories one by one to ensure UUID conversion works
        $categories = [];
        foreach ($categoryIds as $categoryId) {
            $category = $this->entityManager->getRepository(Category::class)
                ->find(Uuid::fromString($categoryId));
            if ($category !== null) {
                $categories[] = $category;
            }
        }

        // Add categories
        foreach ($categories as $category) {
            $companyGroup->addCategory($category);
        }
    }

    /**
     * Extract UUIDs from IRIs or plain UUID strings.
     * Handles various formats:
     * - Plain UUID: "019a9db4-aa45-70b8-bdaf-8ad4151f17a3"
     * - Clean IRI: "/api/companies/019a9db4-aa45-70b8-bdaf-8ad4151f17a3"
     * - Malformed IRI: "/api/departments?companyGroup=.../019a9db4-aadb-7572-be6f-5856b39f7b12"
     *
     * @param array<int, string> $iris
     *
     * @return array<int, string>
     */
    private function extractIdsFromIris(array $iris): array
    {
        return array_map(static function (string $iri): string {
            // Extract UUID using regex pattern (UUIDv7 format: 8-4-4-4-12 hex digits)
            if (preg_match('/([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', $iri, $matches)) {
                return $matches[1];
            }

            // If no UUID pattern found, try basename as fallback
            if (str_contains($iri, '/')) {
                return basename(parse_url($iri, \PHP_URL_PATH) ?: $iri);
            }

            // Otherwise, return as-is
            return $iri;
        }, $iris);
    }
}
