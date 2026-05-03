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

        // Register core SDK services
        $container->register(\InfilePhp\Core\FelConfig::class, \InfilePhp\Core\FelConfig::class)
            ->setFactory([self::class, 'createFelConfig'])
            ->setArguments([$config])
            ->setPublic(true);

        $container->register(\InfilePhp\Core\Http\InfileClient::class, \InfilePhp\Core\Http\InfileClient::class)
            ->setArguments([new \Symfony\Component\DependencyInjection\Reference(\InfilePhp\Core\FelConfig::class)])
            ->setPublic(true);
    }

    public function getAlias(): string
    {
        return 'infile_php';
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function createFelConfig(array $config): \InfilePhp\Core\FelConfig
    {
        return new \InfilePhp\Core\FelConfig(
            nit: $config['nit'] ?? '',
            signUser: $config['credentials']['sign_user'] ?? '',
            signKey: $config['credentials']['sign_key'] ?? '',
            apiUser: $config['credentials']['api_user'] ?? '',
            apiKey: $config['credentials']['api_key'] ?? '',
            environment: \InfilePhp\Core\Enums\Environment::from($config['environment'] ?? 'sandbox'),
            flow: \InfilePhp\Core\Enums\Flow::from($config['flow'] ?? 'unified'),
            emailCopy: $config['email_copy'] ?? '',
            retryTimes: $config['retry']['times'] ?? 3,
            retrySleep: $config['retry']['sleep'] ?? 2,
            fallbackEnabled: $config['fallback']['enabled'] ?? true,
            endpointSign: $config['endpoints']['sign'] ?? 'https://signer-emisores.feel.com.gt/sign_solicitud_firmas/firma_xml',
            endpointCertify: $config['endpoints']['certify'] ?? 'https://certificador.feel.com.gt/fel/certificacion/v2/dte/',
            endpointCancel: $config['endpoints']['cancel'] ?? 'https://certificador.feel.com.gt/fel/anulacion/v2/dte/',
            endpointUnified: $config['endpoints']['unified'] ?? 'https://certificador.feel.com.gt/fel/procesounificado/transaccion/v2/xml',
            endpointNit: $config['endpoints']['nit'] ?? 'https://consultareceptores.feel.com.gt/rest/action',
            endpointCui: $config['endpoints']['cui'] ?? 'https://certificador.feel.com.gt/api/v2/servicios/externos/cui',
            endpointCuiAuth: $config['endpoints']['cui_auth'] ?? 'https://certificador.feel.com.gt/api/v2/servicios/externos/login',
        );
    }
}
