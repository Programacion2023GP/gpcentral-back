<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use App\Models\ApiClient;
use App\Models\ObjResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuthenticateApiClient
{
    public function handle(Request $request, Closure $next, string $ability)
    {
        $clientId = trim((string) $request->header('X-Client-Id'));
        $secret = (string) $request->header('X-API-Key');

        if ($clientId === '' || $secret === '') {
            return ObjResponse::unauthorized('Credenciales de integración requeridas');
        }

        $client = ApiClient::with('system')->where('client_id', $clientId)->first();

        if (!$client || !$client->isUsable() || !Hash::check($secret, $client->secret_hash)) {
            return ObjResponse::unauthorized('Credenciales de integración inválidas');
        }

        if (!$client->hasAbility($ability)) {
            return ObjResponse::forbidden('El cliente no tiene el scope requerido');
        }

        $request->attributes->set('api_client', $client);
        $request->attributes->set('api_client_ability', $ability);

        $response = $next($request);

        if (!$client->last_used_at || $client->last_used_at->lt(now()->subMinutes(5))) {
            $client->forceFill(['last_used_at' => now()])->saveQuietly();
        }

        try {
            ActivityLog::create([
                'action' => 'integration_access',
                'description' => 'Consulta autorizada desde un sistema externo',
                'extra_data' => [
                    'client_id' => $client->client_id,
                    'system_id' => $client->system_id,
                    'ability' => $ability,
                    'status_code' => $response->getStatusCode(),
                ],
                'model_type' => ApiClient::class,
                'model_id' => $client->id,
                'user_id' => null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->path(),
                'method' => $request->method(),
            ]);
        } catch (Throwable $exception) {
            // Una falla de bitácora no debe convertir una lectura autorizada en error 500.
            Log::warning('No se pudo auditar una consulta de integración', [
                'api_client_id' => $client->id,
                'ability' => $ability,
                'error' => $exception->getMessage(),
            ]);
        }

        return $response;
    }
}
