<?php

declare(strict_types=1);

namespace InfilePhp\Symfony\Studio\Http\Controllers\Api;

use InfilePhp\Core\Dte\Invoice;
use InfilePhp\Core\Dte\Item;
use InfilePhp\Core\Dte\Recipient;
use InfilePhp\Core\InfilePhp;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class BuilderController
{
    public function preview(Request $request): JsonResponse
    {
        try {
            $dte = $this->buildDteFromRequest($request);
            $xml = InfilePhp::client()->getUnsignedXml($dte);

            return new JsonResponse([
                'success' => true,
                'xml' => $xml,
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function validate(Request $request): JsonResponse
    {
        try {
            $dte = $this->buildDteFromRequest($request);

            // Core structure validation (throws if missing recipient/items)
            $dte->validate();

            // TODO: Once XSD validation is fully implemented in core, it should be called here
            $xml = InfilePhp::client()->getUnsignedXml($dte);

            return new JsonResponse([
                'success' => true,
                'message' => 'DTE structure is valid.',
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    private function buildDteFromRequest(Request $request): Invoice
    {
        // For now, Studio builder only supports FACT (Invoice)
        $invoice = Invoice::create();

        $payload = json_decode($request->getContent(), true) ?? [];
        
        $recipientData = $payload['recipient'] ?? [];

        if (isset($recipientData['tax_id']) && $recipientData['tax_id'] === 'CF') {
            $invoice->forFinalConsumer();
        } elseif (!empty($recipientData)) {
            $taxId = is_string($recipientData['tax_id'] ?? null) ? $recipientData['tax_id'] : '';
            $name = is_string($recipientData['name'] ?? null) ? $recipientData['name'] : 'Ciudadano';
            $address = is_string($recipientData['address'] ?? null) ? $recipientData['address'] : 'Ciudad';

            $invoice->for(
                Recipient::withTaxId($taxId)
                    ->name($name)
                    ->address($address)
            );
        }

        $items = $payload['items'] ?? [];
        foreach ($items as $itemData) {
            if (!is_array($itemData)) {
                continue;
            }

            $type = is_string($itemData['type'] ?? null) ? $itemData['type'] : 'B';
            $description = is_string($itemData['description'] ?? null) ? $itemData['description'] : 'Item';
            $quantity = (float) ($itemData['quantity'] ?? 1);
            $unitPrice = (float) ($itemData['unit_price'] ?? 0.0);

            if ($type === 'S') {
                $item = Item::service($description);
            } else {
                $item = Item::product($description);
            }

            $item->quantity($quantity)->unitPrice($unitPrice);
            $invoice->add($item);
        }

        return $invoice;
    }
}
