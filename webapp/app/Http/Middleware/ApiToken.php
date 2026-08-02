<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = config('services.api_token');

        if (! $token) {
            return response()->json(['error' => 'API not configured'], 503);
        }

        $provided = $request->header('X-API-Token')
            ?? $request->header('Authorization');

        if (is_string($provided) && str_starts_with($provided, 'Bearer ')) {
            $provided = substr($provided, 7);
        }

        if (! is_string($provided) || ! hash_equals($token, $provided)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
