<?php

declare(strict_types=1);

namespace InfilePhp\Symfony\Messenger;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles IssueDteMessage dispatched through the Symfony Messenger bus.
 * Auto-registered via services.yaml tag: messenger.message_handler.
 */
#[AsMessageHandler]
final class IssueDteHandler
{
    public function __construct()
    {
    }

    public function __invoke(IssueDteMessage $message): void
    {
        $message->dte->issue();
    }
}
