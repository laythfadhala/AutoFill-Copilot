<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InternalServiceMiddleware
{
    /**
     * Handle an incoming request for internal service communication.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if request is from internal Docker network
        $allowedHosts = [
            'profile-service',
            'ai-service',
            'doc-parser',
            'auth-service',
            'website'
        ];

        $host = $request->getHost();
        $userAgent = $request->header('User-Agent', '');

        // For now, allow all internal requests
        // TODO: Implement proper service authentication
        if (app()->environment('local', 'development')) {
            return $next($request);
        }

        // In production, implement JWT or service tokens
        abort(403, 'Access denied');
    }
}
