<?php

declare(strict_types=1);

namespace C3net\CoreBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class C3netCoreBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode('api_platform')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enable_swagger')->defaultTrue()->end()
                        ->scalarNode('title')->defaultValue('C3net Core API')->end()
                        ->scalarNode('version')->defaultValue('1.0.0')->end()
                        ->scalarNode('description')->defaultValue('Comprehensive business and project management API')->end()
                    ->end()
                ->end()
                ->arrayNode('jwt')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultTrue()->end()
                        ->integerNode('ttl')->defaultValue(3600)->end()
                        ->scalarNode('algorithm')->defaultValue('RS256')->end()
                    ->end()
                ->end()
                ->arrayNode('audit')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultTrue()->end()
                        ->arrayNode('ignored_columns')
                            ->scalarPrototype()->end()
                            ->defaultValue(['createdAt', 'updatedAt'])
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('entity_traits')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('auto_uuid')->defaultTrue()->end()
                        ->booleanNode('auto_timestamps')->defaultTrue()->end()
                        ->booleanNode('auto_soft_delete')->defaultTrue()->end()
                        ->booleanNode('auto_blameable')->defaultTrue()->end()
                    ->end()
                ->end()
                ->arrayNode('cors')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultTrue()->end()
                        ->scalarNode('allow_origin')->defaultValue('^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$')->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // Load services configuration
        $container->import('../config/services.yaml');
        
        // Set parameters from configuration
        $container->parameters()
            ->set('c3net_core.api_platform.enable_swagger', $config['api_platform']['enable_swagger'])
            ->set('c3net_core.api_platform.title', $config['api_platform']['title'])
            ->set('c3net_core.api_platform.version', $config['api_platform']['version'])
            ->set('c3net_core.api_platform.description', $config['api_platform']['description'])
            ->set('c3net_core.jwt.enabled', $config['jwt']['enabled'])
            ->set('c3net_core.jwt.ttl', $config['jwt']['ttl'])
            ->set('c3net_core.jwt.algorithm', $config['jwt']['algorithm'])
            ->set('c3net_core.audit.enabled', $config['audit']['enabled'])
            ->set('c3net_core.audit.ignored_columns', $config['audit']['ignored_columns'])
            ->set('c3net_core.entity_traits.auto_uuid', $config['entity_traits']['auto_uuid'])
            ->set('c3net_core.entity_traits.auto_timestamps', $config['entity_traits']['auto_timestamps'])
            ->set('c3net_core.entity_traits.auto_soft_delete', $config['entity_traits']['auto_soft_delete'])
            ->set('c3net_core.entity_traits.auto_blameable', $config['entity_traits']['auto_blameable'])
            ->set('c3net_core.cors.enabled', $config['cors']['enabled'])
            ->set('c3net_core.cors.allow_origin', $config['cors']['allow_origin'])
        ;
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        
        // Add compiler passes if needed
        // $container->addCompilerPass(new CustomCompilerPass());
    }
}