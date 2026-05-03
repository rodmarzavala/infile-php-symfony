<?php

declare(strict_types=1);

namespace InfilePhp\Symfony;

use InfilePhp\Symfony\DependencyInjection\InfilePhpExtension;
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
}
