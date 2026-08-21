<?php

namespace Platform\Placements\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Platform\Placements\DTOs\ContractEventDTO;
use Platform\Placements\Events\ContractRejectedEvent;
use Platform\Placements\Events\ContractSignedEvent;
use Platform\Placements\Models\Contract;

/**
 * Receives callbacks from the external e-signature provider.
 *
 * The provider retries a callback until it gets a 2xx, and does not guarantee
 * that a given event is delivered exactly once.
 */
class SignatureWebhookController
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'event' => ['required', 'string'],
            'document_id' => ['required', 'string'],
            'event_id' => ['nullable', 'string'],
        ]);

        $contract = Contract::query()
            ->where('external_id', $payload['document_id'])
            ->firstOrFail();

        $dto = new ContractEventDTO($contract, $payload['event_id'] ?? null);

        match ($payload['event']) {
            'document.signed' => ContractSignedEvent::dispatch($dto),
            'document.rejected' => ContractRejectedEvent::dispatch($dto),
            default => null,
        };

        return response()->json(['ok' => true]);
    }
}
