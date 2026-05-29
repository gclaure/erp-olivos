<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request): Response
    {
        $user = Auth::user();
        $url = config('fortify.home');

        if ($user && $user->is_super_admin) {
            // Todos los usuarios ahora comparten el dashboard principal ya que es Single Tenant
            $url = config('fortify.home');
        }

        if ($request->header('X-Inertia')) {
            return \Inertia\Inertia::location($url);
        }

        return redirect()->intended($url);
    }
}
