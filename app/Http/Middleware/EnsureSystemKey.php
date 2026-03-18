<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSystemKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (
            !$request->header('System-Key') ||
            $request->header('System-Key') !== config('app.system_key')
        ) {
            // Pour les écrans dlivery (earnings/collection), renvoyer la structure attendue
            // pour éviter un crash côté Flutter (data null).
            if ($request->is('api/v2/delivery-boy/earning/*') || $request->is('api/v2/delivery-boy/collection/*')) {
                return response()->json([
                    'success' => false,
                    'status' => 401,
                    'data' => [],
                    'meta' => [
                        'current_page' => 1,
                        'from' => null,
                        'last_page' => null,
                        'path' => null,
                        'per_page' => null,
                        'to' => null,
                        'total' => 0,
                    ],
                    'message' => 'Request not found!',
                ], 401);
            }

            return response()->json([
                'result' => false,
                'message' => 'Request not found!'
            ]);
        }

        return $next($request);
    }
}
