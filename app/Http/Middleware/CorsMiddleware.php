<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CorsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        \Illuminate\Support\Facades\Log::info('CORS middleware executed for: ' . $request->url());
        $response = $next($request);
        $response->headers->set('Access-Control-Allow-Origin', 'http://localhost:3000');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');

        if ($request->isMethod('OPTIONS')) {
            return response()->json([], 200);
        }
        return $response;
    }
}
