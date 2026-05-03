<?php

declare(strict_types=1);

namespace InfilePhp\Symfony\Messenger;

use InfilePhp\Core\Contracts\DteContract;

/**
 * Messenger message to asynchronously issue a DTE.
 *
 * Dispatch this via the Symfony Messenger bus and IssueDteHandler will process it.
 */
final readonly class IssueDteMessage
{
    public function __construct(
        public readonly DteContract $dte,
    ) {
    }
}
