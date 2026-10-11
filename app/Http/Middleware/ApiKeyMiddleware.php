<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $key = $request->header('X-API-KEY') ?? $request->input('api_key');
        if ($key !== config('services.esp32.key')) {
            return response()->json(['status' => false, 'msg' => 'Unauthorized'], 403);
        }

        return $next($request);
    }
}
