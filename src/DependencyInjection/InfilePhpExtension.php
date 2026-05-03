<?php

declare(strict_types=1);

namespace InfilePhp\Symfony\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Loads infile-php services and maps bundle configuration to DI parameters.
 */
final class InfilePhpExtension extends Extension
{
    /**
     * @param array<string, mixed> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        // Map config tree to container parameters
        $container->setParameter('infile_php.nit', $config['nit'] ?? '');
        $container->setParameter('infile_php.environment', $config['environment'] ?? 'sandbox');
        $container->setParameter('infile_php.flow', $config['flow'] ?? 'unified');
        $container->setParameter('infile_php.credentials', $config['credentials'] ?? []);
        $container->setParameter('infile_php.endpoints', $config['endpoints'] ?? []);
        $container->setParameter('infile_php.retry', $config['retry'] ?? []);
        $container->setParameter('infile_php.fallback', $config['fallback'] ?? []);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../Resources/config'),
        );

        $loader->load('services.yaml');
    }

    public function getAlias(): string
    {
        return 'infile_php';
    }
}
