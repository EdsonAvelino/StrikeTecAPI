<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Config;

class ChangeGuard
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    { 
        if($request->is('fan/*')) {
            Config::set('jwt.user', 'App\FanUser'); 
            Config::set('auth.providers.users.model', \App\FanUser::class);
        }
        return $next($request);
    }
}
