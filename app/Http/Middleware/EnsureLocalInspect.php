<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureLocalInspect
{
    public function handle(Request $request, Closure $next)
    {
        if (!app()->environment(['local', 'testing'])) {
            abort(404);
        }

        return $next($request);
    }
}
