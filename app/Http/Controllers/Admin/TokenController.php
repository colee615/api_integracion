<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\Company;
use App\Services\TrialTokenPurger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TokenController extends Controller
{
    public function index(): View
    {
        return view('admin.tokens.index', [
            'companies' => Company::query()->orderBy('name')->get(),
            'tokens' => ApiToken::query()
                ->with('company')
                ->latest()
                ->get(),
            'apiCatalog' => $this->apiCatalog(),
        ]);
    }

    private function apiCatalog(): array
    {
        return [
            ['group' => 'Acceso e integración', 'method' => 'GET', 'path' => '/api/v1/me', 'description' => 'Identifica la empresa asociada al token.', 'ability' => 'packages:read'],
            ['group' => 'Acceso e integración', 'method' => 'GET', 'path' => '/api/v1/integration/context', 'description' => 'Consulta el contexto y las capacidades disponibles para la integración.', 'ability' => 'packages:read'],
            ['group' => 'Acceso e integración', 'method' => 'POST', 'path' => '/api/v1/integration/bulk', 'description' => 'Registra información de integración en lote.', 'ability' => 'documents:write'],

            ['group' => 'Despachos y sacas', 'method' => 'GET', 'path' => '/api/v1/cn31/manifests', 'description' => 'Lista despachos CN31; admite filtros por número y fechas.', 'ability' => 'documents:read'],
            ['group' => 'Despachos y sacas', 'method' => 'POST', 'path' => '/api/v1/cn31/manifests', 'description' => 'Registra un despacho CN31.', 'ability' => 'documents:write'],
            ['group' => 'Despachos y sacas', 'method' => 'GET', 'path' => '/api/v1/cn31/manifests/{cn31Number}', 'description' => 'Consulta el despacho, sus sacas y el resumen de entrega.', 'ability' => 'documents:read'],
            ['group' => 'Despachos y sacas', 'method' => 'POST', 'path' => '/api/v1/cn33/bags/{bagNumber}/packages', 'description' => 'Asocia paquetes a una saca CN33.', 'ability' => 'documents:write'],
            ['group' => 'Despachos y sacas', 'method' => 'GET', 'path' => '/api/v1/cn33/bags/{bagNumber}', 'description' => 'Consulta la saca, los paquetes asociados y sus totales de entrega.', 'ability' => 'documents:read'],
            ['group' => 'Despachos y sacas', 'method' => 'GET', 'path' => '/api/v1/cn33/bags/{bagNumber}/movements', 'description' => 'Consulta la bitácora de eventos de la saca.', 'ability' => 'tracking:read'],
            ['group' => 'Despachos y sacas', 'method' => 'POST', 'path' => '/api/v1/cn33/bags/{bagNumber}/movements', 'description' => 'Registra recepción o apertura de una saca.', 'ability' => 'tracking:events:write'],
            ['group' => 'Paquetes', 'method' => 'POST', 'path' => '/api/v1/cn22/shipments', 'description' => 'Registra una prealerta CN22 y su información de envío.', 'ability' => 'documents:write'],

            ['group' => 'Paquetes', 'method' => 'GET', 'path' => '/api/v1/packages', 'description' => 'Lista paquetes de la empresa con filtros de búsqueda.', 'ability' => 'tracking:read'],
            ['group' => 'Paquetes', 'method' => 'POST', 'path' => '/api/v1/packages', 'description' => 'Registra un paquete.', 'ability' => 'tracking:write'],
            ['group' => 'Paquetes', 'method' => 'GET', 'path' => '/api/v1/packages/{trackingCode}', 'description' => 'Consulta el paquete y su historial visible.', 'ability' => 'tracking:read'],
            ['group' => 'Paquetes', 'method' => 'GET', 'path' => '/api/v1/packages/{trackingCode}/movements', 'description' => 'Consulta los movimientos públicos del paquete.', 'ability' => 'tracking:read'],
            ['group' => 'Paquetes', 'method' => 'POST', 'path' => '/api/v1/packages/{trackingCode}/movements', 'description' => 'Registra un evento de tracking del paquete.', 'ability' => 'tracking:events:write'],

            ['group' => 'Webhooks', 'method' => 'GET', 'path' => '/api/v1/webhooks', 'description' => 'Lista los webhooks configurados para la empresa.', 'ability' => 'webhooks:manage'],
            ['group' => 'Webhooks', 'method' => 'POST', 'path' => '/api/v1/webhooks', 'description' => 'Crea un webhook para recibir notificaciones.', 'ability' => 'webhooks:manage'],
            ['group' => 'Webhooks', 'method' => 'PATCH', 'path' => '/api/v1/webhooks/{webhookEndpoint}', 'description' => 'Actualiza un webhook.', 'ability' => 'webhooks:manage'],
            ['group' => 'Webhooks', 'method' => 'GET', 'path' => '/api/v1/webhooks/{webhookEndpoint}/deliveries', 'description' => 'Consulta los intentos de entrega de un webhook.', 'ability' => 'webhooks:manage'],
            ['group' => 'Webhooks', 'method' => 'POST', 'path' => '/api/v1/webhooks/{webhookEndpoint}/deliveries/{webhookDelivery}/retry', 'description' => 'Reintenta una notificación fallida.', 'ability' => 'webhooks:manage'],
            ['group' => 'Webhooks', 'method' => 'POST', 'path' => '/api/v1/webhooks/{webhookEndpoint}/test', 'description' => 'Envía una notificación de prueba.', 'ability' => 'webhooks:manage'],
            ['group' => 'Webhooks', 'method' => 'DELETE', 'path' => '/api/v1/webhooks/{webhookEndpoint}', 'description' => 'Elimina un webhook.', 'ability' => 'webhooks:manage'],
        ];
    }

    public function openApi(): BinaryFileResponse
    {
        return response()->download(
            base_path('docs/openapi-tracking.yaml'),
            'api-integracion-openapi.yaml',
            ['Content-Type' => 'application/yaml; charset=UTF-8'],
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', Rule::exists('companies', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'token_type' => ['required', Rule::in(['production', 'trial'])],
            'starts_at' => ['required', 'date'],
            'expires_at' => ['required', 'date', 'after:starts_at'],
            'abilities' => ['sometimes', 'array', 'min:1'],
            'abilities.*' => ['required', 'string', Rule::in([
                'tracking:read', 'tracking:write', 'tracking:events:write',
                'documents:read', 'documents:write', 'webhooks:manage',
            ])],
        ]);

        $company = Company::query()->findOrFail($validated['company_id']);

        [, $plainTextToken] = ApiToken::issue(
            $company,
            $validated['name'],
            now()->parse($validated['starts_at']),
            now()->parse($validated['expires_at']),
            array_values(array_unique($validated['abilities'] ?? ['tracking:read', 'tracking:write'])),
            $validated['token_type']
        );

        return back()->with('new_token', [
            'company' => $company->name,
            'name' => $validated['name'],
            'token' => $plainTextToken,
            'token_type' => $validated['token_type'],
            'abilities' => array_values(array_unique($validated['abilities'] ?? ['tracking:read', 'tracking:write'])),
        ])->with('status', 'Token exclusivo generado correctamente.');
    }

    public function revoke(ApiToken $token, TrialTokenPurger $purger): RedirectResponse
    {
        DB::transaction(function () use ($token, $purger): void {
            $token->forceFill([
                'revoked_at' => now(),
            ])->save();

            $purger->purge($token);
        });

        return back()->with('status', 'Token revocado correctamente.');
    }

    public function rotate(ApiToken $token, TrialTokenPurger $purger): RedirectResponse
    {
        $rotatedTrial = $token->isTrial();
        $startsAt = $token->starts_at?->isFuture() ? $token->starts_at : now();
        $expiresAt = $token->expires_at?->isAfter($startsAt)
            ? $token->expires_at
            : $startsAt->copy()->addDays(30);

        [$replacement, $plainTextToken] = DB::transaction(function () use (
            $token,
            $purger,
            $startsAt,
            $expiresAt
        ): array {
            [$replacement, $plainTextToken] = ApiToken::issue(
                $token->company,
                $token->name,
                $startsAt,
                $expiresAt,
                $token->abilities ?? [],
                $token->token_type,
            );

            $token->forceFill(['revoked_at' => now()])->save();
            $purger->purge($token);

            return [$replacement, $plainTextToken];
        });

        return back()->with('new_token', [
            'company' => $token->company->name,
            'name' => $replacement->name,
            'token' => $plainTextToken,
            'token_type' => $replacement->token_type,
            'abilities' => $replacement->abilities ?? [],
        ])->with('status', $rotatedTrial
            ? 'Credencial de prueba rotada. El token anterior fue revocado y sus datos asociados fueron purgados.'
            : 'Credencial rotada. El token anterior fue revocado.');
    }

    public function reactivate(ApiToken $token): RedirectResponse
    {
        $token->forceFill([
            'revoked_at' => null,
        ])->save();

        return back()->with('status', 'Token reactivado correctamente.');
    }

    public function extend(Request $request, ApiToken $token): RedirectResponse
    {
        $validated = $request->validate([
            'starts_at' => ['required', 'date'],
            'expires_at' => ['required', 'date', 'after:starts_at'],
        ]);

        $token->forceFill([
            'starts_at' => now()->parse($validated['starts_at']),
            'expires_at' => now()->parse($validated['expires_at']),
        ])->save();

        return back()->with('status', 'Vigencia del token actualizada correctamente.');
    }

    public function updateSettings(Request $request, ApiToken $token, TrialTokenPurger $purger): RedirectResponse
    {
        $validated = $request->validate([
            'starts_at' => ['required', 'date'],
            'expires_at' => ['required', 'date', 'after:starts_at'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        DB::transaction(function () use ($token, $validated, $purger): void {
            $token->forceFill([
                'starts_at' => now()->parse($validated['starts_at']),
                'expires_at' => now()->parse($validated['expires_at']),
                'revoked_at' => $validated['status'] === 'inactive' ? now() : null,
            ])->save();

            if ($validated['status'] === 'inactive') {
                $purger->purge($token);
            }
        });

        return back()->with('status', 'Configuracion del token actualizada correctamente.');
    }

    public function destroy(ApiToken $token, TrialTokenPurger $purger): RedirectResponse
    {
        DB::transaction(function () use ($token, $purger): void {
            $purger->purge($token);
            $token->delete();
        });

        return back()->with('status', 'Token eliminado correctamente.');
    }
}
