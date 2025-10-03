<?php

declare(strict_types=1);

namespace C3net\CoreBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class C3netCoreBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // Load services configuration
        $container->import('../config/services.yaml');
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Add compiler passes if needed
        // $container->addCompilerPass(new CustomCompilerPass());
    }
}
