<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

/**
 * @template-extends NestedTreeRepository<Category>
 */
final class CategoryRepository extends NestedTreeRepository
{
}
