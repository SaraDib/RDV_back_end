<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class WorkerToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('X-WORKER-TOKEN');
        if (!$token || $token !== env('WORKER_TOKEN')) {
            return response()->json(['message' => 'Unauthorized worker'], 401);
        }
        return $next($request);
    }
}
