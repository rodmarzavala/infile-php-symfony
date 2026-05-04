<?php

declare(strict_types=1);

namespace InfilePhp\Symfony\EventSubscriber;

use InfilePhp\Core\Events\DteCancelled;
use InfilePhp\Core\Events\DteFailed;
use InfilePhp\Core\Events\DteIssued;
use InfilePhp\Core\Events\FallbackActivated;
use InfilePhp\Symfony\Studio\Storage\StudioRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class StudioEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private StudioRepository $repository
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DteIssued::class => 'onDteIssued',
            DteFailed::class => 'onDteFailed',
            FallbackActivated::class => 'onFallbackActivated',
            DteCancelled::class => 'onDteCancelled',
        ];
    }

    public function onDteIssued(DteIssued $event): void
    {
        $this->repository->logTransaction([
            'uuid' => $event->uuid,
            'serie' => $event->serie,
            'numero' => $event->numero,
            'dte_type' => $event->dteType->value,
            'recipient_tax_id' => $event->recipientTaxId,
            'idempotency_key' => $event->idempotencyKey,
            'status' => 'issued',
            'payload' => [
                'event' => 'DteIssued',
                'xml_certified' => $event->xmlCertified,
            ],
            'error_message' => null,
        ]);
    }

    public function onDteFailed(DteFailed $event): void
    {
        $this->repository->logTransaction([
            'dte_type' => $event->dteType->value,
            'recipient_tax_id' => null,
            'idempotency_key' => $event->idempotencyKey,
            'status' => 'failed',
            'payload' => [
                'event' => 'DteFailed',
                'exception_class' => $event->previous ? get_class($event->previous) : null,
            ],
            'error_message' => $event->errorMessage,
        ]);
    }

    public function onFallbackActivated(FallbackActivated $event): void
    {
        $this->repository->logTransaction([
            'dte_type' => null,
            'recipient_tax_id' => null,
            'idempotency_key' => $event->idempotencyKey,
            'status' => 'pending',
            'payload' => null,
            'error_message' => 'Contingencia CAFE activada. Se encoló para reintento.',
        ]);
    }

    public function onDteCancelled(DteCancelled $event): void
    {
        $this->repository->logTransaction([
            'uuid' => $event->uuid,
            'status' => 'cancelled',
            'payload' => [
                'event' => 'DteCancelled',
                'reason' => $event->reason,
            ],
            'error_message' => null,
        ]);
    }
}
