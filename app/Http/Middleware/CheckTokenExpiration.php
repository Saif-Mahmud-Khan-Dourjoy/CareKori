<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CheckTokenExpiration
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {


        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $token = $user->currentAccessToken();

        if (!$token) {
            return response()->json(['message' => 'Token not found'], 401);
        }

       
        $expirationMinutes = env('SANCTUM_TOKEN_EXPIRATION', 60);

        $expiresAt = $token->created_at->addMinutes($expirationMinutes);

        if ($expiresAt->isPast()) {
            return response()->json(['message' => 'Token expired. Please refresh your token.'], 401);
        }

        return $next($request);
    }
}