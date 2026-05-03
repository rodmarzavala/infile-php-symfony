<?php

declare(strict_types=1);

namespace InfilePhp\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Defines the bundle configuration tree for infile_php.
 *
 * Example infile_php.yaml:
 *   infile_php:
 *     nit: '%env(FEL_NIT)%'
 *     environment: '%env(FEL_ENV)%'
 *     credentials:
 *       sign_user: '%env(FEL_SIGN_USER)%'
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('infile_php');
        $root        = $treeBuilder->getRootNode();

        $root
            ->children()
                ->scalarNode('nit')->defaultValue('')->end()
                ->scalarNode('environment')->defaultValue('sandbox')->end()
                ->scalarNode('flow')->defaultValue('unified')->end()
                ->arrayNode('credentials')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('sign_user')->defaultValue('')->end()
                        ->scalarNode('sign_key')->defaultValue('')->end()
                        ->scalarNode('api_user')->defaultValue('')->end()
                        ->scalarNode('api_key')->defaultValue('')->end()
                    ->end()
                ->end()
                ->arrayNode('endpoints')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('sign')->defaultValue('https://signer-emisores.feel.com.gt/sign_solicitud_firmas/firma_xml')->end()
                        ->scalarNode('certify')->defaultValue('https://certificador.feel.com.gt/fel/certificacion/v2/dte/')->end()
                        ->scalarNode('cancel')->defaultValue('https://certificador.feel.com.gt/fel/anulacion/v2/dte/')->end()
                        ->scalarNode('unified')->defaultValue('https://certificador.feel.com.gt/fel/procesounificado/transaccion/v2/xml')->end()
                        ->scalarNode('nit')->defaultValue('https://consultareceptores.feel.com.gt/rest/action')->end()
                        ->scalarNode('cui')->defaultValue('https://certificador.feel.com.gt/api/v2/servicios/externos/cui')->end()
                        ->scalarNode('cui_auth')->defaultValue('https://certificador.feel.com.gt/api/v2/servicios/externos/login')->end()
                    ->end()
                ->end()
                ->arrayNode('retry')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('times')->defaultValue(3)->end()
                        ->integerNode('sleep')->defaultValue(2)->end()
                    ->end()
                ->end()
                ->arrayNode('fallback')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultTrue()->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
