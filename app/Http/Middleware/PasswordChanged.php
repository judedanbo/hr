<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PasswordChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth()->user()->password_change_at == null) {
            return redirect(route('change-password.index'));
        }

        return $next($request);
    }
}
