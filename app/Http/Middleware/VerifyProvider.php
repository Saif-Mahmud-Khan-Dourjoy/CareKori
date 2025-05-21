<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class VerifyProvider
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $deniedRoles = ['moderator', 'customer', 'super admin'];

        if (auth()->check() && in_array(Str::lower(auth()->user()->role->name), $deniedRoles)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        return $next($request);
    }
}