<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DisableBackCache
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $contentType = (string) $response->headers->get('Content-Type');

        // Solo deshabilitar caché para documentos HTML (evita problemas con tokens CSRF cacheados)
        if (str_contains($contentType, 'text/html')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
        } 
        // Habilitar caché agresiva para assets estáticos si son servidos a través de Laravel
        elseif (str_contains($contentType, 'text/css') || 
                str_contains($contentType, 'application/javascript') || 
                str_contains($contentType, 'image/') || 
                str_contains($contentType, 'font/')) {
            $response->headers->set('Cache-Control', 'public, max-age=2592000, immutable');
        }

        return $response;
    }
}
