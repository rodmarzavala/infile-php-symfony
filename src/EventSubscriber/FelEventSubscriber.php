<?php

declare(strict_types=1);

namespace InfilePhp\Symfony\EventSubscriber;

use InfilePhp\Core\Events\DteCancelled;
use InfilePhp\Core\Events\DteFailed;
use InfilePhp\Core\Events\DteIssued;
use InfilePhp\Core\Events\FallbackActivated;
use InfilePhp\Core\Events\InfileServiceDown;
use InfilePhp\Core\Events\InfileServiceRestored;
use InfilePhp\Symfony\DataCollector\FelDataCollector;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to all core FEL events, logs them via Monolog, and feeds the DataCollector.
 */
final class FelEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly FelDataCollector $collector,
    ) {
    }

    /**
     * @return array<class-string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            DteIssued::class            => 'onDteIssued',
            DteFailed::class            => 'onDteFailed',
            DteCancelled::class         => 'onDteCancelled',
            FallbackActivated::class    => 'onFallbackActivated',
            InfileServiceDown::class    => 'onServiceDown',
            InfileServiceRestored::class => 'onServiceRestored',
        ];
    }

    public function onDteIssued(DteIssued $event): void
    {
        $this->logger->info('DTE issued successfully.', [
            'uuid'      => $event->uuid,
            'type'      => $event->dteType->value,
            'serie'     => $event->serie,
            'numero'    => $event->numero,
            'recipient' => $event->recipientTaxId,
        ]);

        $this->collector->recordIssued(
            event: $event,
            timings: [],
            xmlSent: '',
            xmlCertified: '',
        );
    }

    public function onDteFailed(DteFailed $event): void
    {
        $this->logger->error('DTE certification failed.', [
            'type'    => $event->dteType->value,
            'message' => $event->errorMessage,
            'key'     => $event->idempotencyKey,
        ]);

        $this->collector->recordFailed($event);
    }

    public function onDteCancelled(DteCancelled $event): void
    {
        $this->logger->info('DTE cancelled.', [
            'uuid'   => $event->uuid,
            'type'   => $event->dteType->value,
            'reason' => $event->reason,
        ]);
    }

    public function onFallbackActivated(FallbackActivated $event): void
    {
        $this->logger->warning('FEL fallback (CAFE contingency) activated.', [
            'type'  => $event->dteType->value,
            'cafe'  => $event->cafe,
            'reason' => $event->reason,
        ]);
    }

    public function onServiceDown(InfileServiceDown $event): void
    {
        $this->logger->critical('Infile service is unreachable.', [
            'endpoint' => $event->endpoint,
            'message'  => $event->errorMessage,
        ]);
    }

    public function onServiceRestored(InfileServiceRestored $event): void
    {
        $this->logger->info('Infile service restored.', [
            'endpoint'         => $event->endpoint,
            'downtime_seconds' => $event->downtimeSeconds,
        ]);
    }
}
