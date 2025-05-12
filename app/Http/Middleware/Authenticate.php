<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // If it's an API request, return a 401 Unauthorized response
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Not authenticated or unauthorized'], 401);
        }

        // For non-API requests (like web), redirect to the login page
        return route('login');
    }
}