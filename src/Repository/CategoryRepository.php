<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Repository;

use C3net\CoreBundle\Entity\Category;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

/**
 * @template-extends NestedTreeRepository<Category>
 */
final class CategoryRepository extends NestedTreeRepository
{
}
