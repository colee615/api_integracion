<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cn31Bag;
use App\Models\Cn31BagMovement;
use App\Services\BagTrackingEventWriter;
use App\Support\ApiTokenContext;
use App\Support\IpsEventCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BagMovementController extends Controller
{
    public function index(Request $request, string $bagReference): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $bag = $this->resolveBag($request, $bagReference);
        $movements = $bag->movements()
            ->reorder('occurred_at')
            ->orderBy('id')
            ->paginate($validated['per_page'] ?? 50);

        return response()->json([
            'data' => $movements->getCollection()->map(fn (Cn31BagMovement $movement) => $this->transformMovement($movement))->values(),
            'current_status' => $bag->operational_status,
            'current_status_label' => __('api.statuses.'.$bag->operational_status),
            'delivery_status' => $bag->delivery_status,
            'delivery_status_label' => __('api.statuses.'.$bag->delivery_status),
            'meta' => [
                'current_page' => $movements->currentPage(),
                'per_page' => $movements->perPage(),
                'total' => $movements->total(),
                'last_page' => $movements->lastPage(),
            ],
        ]);
    }

    public function store(
        Request $request,
        string $bagReference,
        BagTrackingEventWriter $writer
    ): JsonResponse {
        $apiToken = ApiTokenContext::currentToken($request);
        $bag = $this->resolveBag($request, $bagReference);
        $validated = $request->validate([
            'ips_operation_code' => ['required', 'string', Rule::in(array_keys(IpsEventCatalog::bagEvents()))],
            'location_code' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9._:-]+$/'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'physical_confirmation' => ['nullable', 'boolean'],
            'occurred_at' => [
                'nullable',
                'date',
                'regex:/^\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}(?:\\.\\d+)?(?:Z|[+-]\\d{2}:\\d{2})$/',
            ],
            'source_reference' => ['nullable', 'string', 'max:120'],
            'meta' => ['nullable', 'array'],
        ]);

        $operationCode = strtoupper(trim($validated['ips_operation_code']));
        $operation = IpsEventCatalog::bagEvent($operationCode);
        if (! empty($operation['requires_physical_confirmation'])
            && filter_var($validated['physical_confirmation'] ?? false, FILTER_VALIDATE_BOOLEAN) !== true) {
            throw ValidationException::withMessages([
                'physical_confirmation' => ['Confirme que la saca llegó físicamente.'],
            ]);
        }

        $idempotencyKey = $request->header('Idempotency-Key') ?: ($validated['source_reference'] ?? null);
        if ($idempotencyKey !== null && ! preg_match('/^[A-Za-z0-9._:-]{1,120}$/', $idempotencyKey)) {
            return response()->json([
                'code' => 'INVALID_IDEMPOTENCY_KEY',
                'message' => __('api.errors.invalid_idempotency_key'),
            ], 422);
        }

        $result = $writer->record($bag, [
            ...$validated,
            'ips_operation_code' => $operationCode,
            'physical_confirmation' => isset($validated['physical_confirmation'])
                ? filter_var($validated['physical_confirmation'], FILTER_VALIDATE_BOOLEAN)
                : null,
            'created_by_api_token_id' => ApiTokenContext::ownershipAttributes($apiToken)['created_by_api_token_id'],
        ], 'company_api', $idempotencyKey);

        if ($result['conflict']) {
            return response()->json([
                'code' => 'IDEMPOTENCY_KEY_REUSED',
                'message' => __('api.errors.idempotency_conflict'),
            ], 409);
        }

        $bag = $bag->fresh();

        return response()->json([
            'message' => $result['replayed'] ? __('api.messages.movement_replayed') : __('api.messages.movement_registered'),
            'data' => $this->transformMovement($result['movement']),
            'replayed' => $result['replayed'],
            'current_status' => $bag->operational_status,
            'current_status_label' => __('api.statuses.'.$bag->operational_status),
            'delivery_status' => $bag->delivery_status,
            'delivery_status_label' => __('api.statuses.'.$bag->delivery_status),
        ], $result['replayed'] ? 200 : 201);
    }

    private function resolveBag(Request $request, string $bagReference): Cn31Bag
    {
        $company = $request->attributes->get('currentCompany');
        $apiToken = ApiTokenContext::currentToken($request);

        $matches = ApiTokenContext::scopeToToken(
            Cn31Bag::query()->where('company_id', $company->id),
            $apiToken
        )
            ->where('bag_number', $bagReference)
            ->firstOrFail();

        return $matches;
    }

    private function transformMovement(Cn31BagMovement $movement): array
    {
        $operation = IpsEventCatalog::bagEvent((string) $movement->ips_operation_code);

        return [
            'id' => $movement->id,
            'event_id' => $movement->event_id,
            'event_code' => $movement->event_code,
            'ips_operation_code' => $movement->ips_operation_code,
            'ips_event_type_cd' => $movement->ips_event_type_cd,
            'event_name' => $operation['name'] ?? $movement->event_code,
            'status' => $movement->status,
            'location_code' => $movement->location_code,
            'location' => $movement->location,
            'description' => $movement->description,
            'occurred_at' => $movement->occurred_at?->utc()->toIso8601String(),
            'recorded_at' => $movement->recorded_at?->utc()->toIso8601String(),
            'source' => $movement->source_type,
            'source_reference' => $movement->source_reference,
            'physical_confirmation' => $movement->physical_confirmation,
            'meta' => $movement->meta ?? [],
        ];
    }
}
