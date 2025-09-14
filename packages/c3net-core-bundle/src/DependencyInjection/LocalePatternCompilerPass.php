<?php

declare(strict_types=1);

namespace C3net\CoreBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Compiler Pass to automatically generate locale patterns from app.locales.
 */
class LocalePatternCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('app.locales')) {
            return;
        }

        $locales = $container->getParameter('app.locales');
        if (!is_array($locales)) {
            return;
        }
        $pattern = implode('|', $locales);

        // Set the generated pattern as a parameter
        $container->setParameter('app.locales.pattern', $pattern);
    }
}
