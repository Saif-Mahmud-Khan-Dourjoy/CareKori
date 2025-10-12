<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAuthor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
       
        $user = Auth::user();

        
        if (!in_array($user->role->name, $roles)) {
            return response()->json([
                'error' => 'Unauthorized. You do not have the required role.'
            ], 403);
        }

        
        return $next($request);
    }
}