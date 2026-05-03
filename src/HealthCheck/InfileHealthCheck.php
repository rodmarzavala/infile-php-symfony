<?php

declare(strict_types=1);

namespace InfilePhp\Symfony\HealthCheck;

use InfilePhp\Core\InfilePhp;

/**
 * Health check for the Infile certification endpoint.
 * Compatible with symfony/health-check and any PSR health check implementation.
 */
final class InfileHealthCheck
{
    /**
     * Return true when the Infile certify endpoint responds within timeout.
     */
    public function isHealthy(): bool
    {
        try {
            InfilePhp::client()->ping();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Return the response time in milliseconds, or null if unreachable.
     */
    public function responseTimeMs(): ?int
    {
        try {
            return InfilePhp::client()->ping();
        } catch (\Throwable) {
            return null;
        }
    }
}
