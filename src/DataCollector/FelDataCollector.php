<?php

declare(strict_types=1);

namespace InfilePhp\Symfony\DataCollector;

use InfilePhp\Core\Events\DteFailed;
use InfilePhp\Core\Events\DteIssued;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Symfony Profiler DataCollector for FEL activity.
 *
 * Shows per-request:
 * - Number of DTEs issued / failed
 * - Timeline per DTE (sign ms, certify ms, total ms)
 * - XML payloads (collapsible)
 * - Infile errors in human-readable form
 * - Replay button
 */
final class FelDataCollector extends AbstractDataCollector
{
    /** @var list<array<string, mixed>> */
    private array $dtes = [];

    /** @var list<array<string, mixed>> */
    private array $errors = [];

    public function getName(): string
    {
        return 'infile_php.fel';
    }

    /**
     * Record a successfully issued DTE for the profiler panel.
     *
     * @param array<string, mixed> $timings sign_ms, certify_ms, total_ms
     */
    public function recordIssued(DteIssued $event, array $timings, string $xmlSent, string $xmlCertified): void
    {
        $this->dtes[] = [
            'uuid'          => $event->uuid,
            'serie'         => $event->serie,
            'numero'        => $event->numero,
            'type'          => $event->dteType->value,
            'recipient'     => $event->recipientTaxId,
            'status'        => 'issued',
            'timings'       => $timings,
            'xml_sent'      => $xmlSent,
            'xml_certified' => $xmlCertified,
        ];
    }

    /**
     * Record a failed DTE attempt for the profiler panel.
     */
    public function recordFailed(DteFailed $event): void
    {
        $this->errors[] = [
            'type'    => $event->dteType->value,
            'message' => $event->errorMessage,
        ];
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        $this->data = [
            'dtes'   => $this->dtes,
            'errors' => $this->errors,
        ];
    }

    public function getIssuedCount(): int
    {
        /** @var list<array<string, mixed>> $dtes */
        $dtes = $this->data['dtes'] ?? [];

        return count($dtes);
    }

    public function getFailedCount(): int
    {
        /** @var list<array<string, mixed>> $errors */
        $errors = $this->data['errors'] ?? [];

        return count($errors);
    }

    /** @return list<array<string, mixed>> */
    public function getDtes(): array
    {
        /** @var list<array<string, mixed>> $dtes */
        $dtes = $this->data['dtes'] ?? [];

        return $dtes;
    }

    /** @return list<array<string, mixed>> */
    public function getErrors(): array
    {
        /** @var list<array<string, mixed>> $errors */
        $errors = $this->data['errors'] ?? [];

        return $errors;
    }
}
