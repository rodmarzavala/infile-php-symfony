<?php

declare(strict_types=1);

namespace InfilePhp\Symfony\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Loads infile-php services and maps bundle configuration to DI parameters.
 */
final class InfilePhpExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        // Explicitly register the Twig namespace so it's always found regardless of Symfony version
        $container->prependExtensionConfig('twig', [
            'paths' => [
                __DIR__ . '/../../Resources/views' => 'InfilePhp',
            ],
        ]);
    }
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

        $studioConfig = $config['studio'] ?? [];
        $container->setParameter('infile_php.studio.enabled', $studioConfig['enabled'] ?? true);
        $container->setParameter('infile_php.studio.driver', $studioConfig['driver'] ?? 'sqlite');

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../Resources/config'),
        );

        $loader->load('services.yaml');

        // Alias PSR interfaces so we can fetch them in the bundle boot
        $container->setAlias('infile_php.http_client', \Psr\Http\Client\ClientInterface::class)->setPublic(true);
        $container->setAlias('infile_php.request_factory', \Psr\Http\Message\RequestFactoryInterface::class)->setPublic(true);
        $container->setAlias('infile_php.stream_factory', \Psr\Http\Message\StreamFactoryInterface::class)->setPublic(true);

        // Register core SDK services
        $container->register(\InfilePhp\Core\FelConfig::class, \InfilePhp\Core\FelConfig::class)
            ->setFactory([self::class, 'createFelConfig'])
            ->setArguments([$config])
            ->setPublic(true);

        $container->register(\InfilePhp\Core\Http\InfileClient::class, \InfilePhp\Core\Http\InfileClient::class)
            ->setArguments([
                new \Symfony\Component\DependencyInjection\Reference(\InfilePhp\Core\FelConfig::class),
                new \Symfony\Component\DependencyInjection\Reference(\Psr\Http\Client\ClientInterface::class),
                new \Symfony\Component\DependencyInjection\Reference(\Psr\Http\Message\RequestFactoryInterface::class),
                new \Symfony\Component\DependencyInjection\Reference(\Psr\Http\Message\StreamFactoryInterface::class),
            ])
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
        /** @var array<string, mixed> $credentials */
        $credentials = is_array($config['credentials'] ?? null) ? $config['credentials'] : [];
        /** @var array<string, mixed> $retry */
        $retry = is_array($config['retry'] ?? null) ? $config['retry'] : [];
        /** @var array<string, mixed> $fallback */
        $fallback = is_array($config['fallback'] ?? null) ? $config['fallback'] : [];
        /** @var array<string, mixed> $endpoints */
        $endpoints = is_array($config['endpoints'] ?? null) ? $config['endpoints'] : [];

        $env = is_string($config['environment'] ?? null) ? $config['environment'] : 'sandbox';
        $flow = is_string($config['flow'] ?? null) ? $config['flow'] : 'unified';

        return new \InfilePhp\Core\FelConfig(
            nit: is_string($config['nit'] ?? null) ? $config['nit'] : '',
            signUser: is_string($credentials['sign_user'] ?? null) ? $credentials['sign_user'] : '',
            signKey: is_string($credentials['sign_key'] ?? null) ? $credentials['sign_key'] : '',
            apiUser: is_string($credentials['api_user'] ?? null) ? $credentials['api_user'] : '',
            apiKey: is_string($credentials['api_key'] ?? null) ? $credentials['api_key'] : '',
            environment: \InfilePhp\Core\Enums\Environment::from($env),
            flow: \InfilePhp\Core\Enums\Flow::from($flow),
            emailCopy: is_string($config['email_copy'] ?? null) ? $config['email_copy'] : '',
            retryTimes: is_int($retry['times'] ?? null) ? $retry['times'] : 3,
            retrySleep: is_int($retry['sleep'] ?? null) ? $retry['sleep'] : 2,
            fallbackEnabled: is_bool($fallback['enabled'] ?? null) ? $fallback['enabled'] : true,
            endpointSign: is_string($endpoints['sign'] ?? null) ? $endpoints['sign'] : 'https://signer-emisores.feel.com.gt/sign_solicitud_firmas/firma_xml',
            endpointCertify: is_string($endpoints['certify'] ?? null) ? $endpoints['certify'] : 'https://certificador.feel.com.gt/fel/certificacion/v2/dte/',
            endpointCancel: is_string($endpoints['cancel'] ?? null) ? $endpoints['cancel'] : 'https://certificador.feel.com.gt/fel/anulacion/v2/dte/',
            endpointUnified: is_string($endpoints['unified'] ?? null) ? $endpoints['unified'] : 'https://certificador.feel.com.gt/fel/procesounificado/transaccion/v2/xml',
            endpointNit: is_string($endpoints['nit'] ?? null) ? $endpoints['nit'] : 'https://consultareceptores.feel.com.gt/rest/action',
            endpointCui: is_string($endpoints['cui'] ?? null) ? $endpoints['cui'] : 'https://certificador.feel.com.gt/api/v2/servicios/externos/cui',
            endpointCuiAuth: is_string($endpoints['cui_auth'] ?? null) ? $endpoints['cui_auth'] : 'https://certificador.feel.com.gt/api/v2/servicios/externos/login',
        );
    }
}
