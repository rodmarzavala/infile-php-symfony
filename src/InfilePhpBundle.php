<?php

declare(strict_types=1);

namespace InfilePhp\Symfony;

use InfilePhp\Core\FelConfig;
use InfilePhp\Core\InfilePhp;
use InfilePhp\Symfony\DependencyInjection\InfilePhpExtension;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * InfilePhpBundle — Symfony integration for the infile-php FEL SDK.
 *
 * Register in config/bundles.php:
 *   InfilePhp\Symfony\InfilePhpBundle::class => ['all' => true],
 */
final class InfilePhpBundle extends Bundle
{
    public function getContainerExtension(): ExtensionInterface
    {
        return new InfilePhpExtension();
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
    }

    public function boot(): void
    {
        /** @var FelConfig $config */
        $config = $this->container->get(FelConfig::class);

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $this->container->get('event_dispatcher');

        InfilePhp::configure($config, $dispatcher);
    }
}
