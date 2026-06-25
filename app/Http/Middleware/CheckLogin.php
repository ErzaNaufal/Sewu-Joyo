<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckLogin
{
    public function handle(
        Request $request,
        Closure $next
    ): Response
    {
        if (!session('login')) {

            return redirect('/login')
                ->with(
                    'error',
                    'Silakan login terlebih dahulu'
                );
        }

        return $next($request);
    }
}