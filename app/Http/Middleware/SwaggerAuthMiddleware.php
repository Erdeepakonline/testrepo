<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SwaggerAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (isset($request->username) && isset($request->password)) {
            if ($request->username == config('app.swagger_username') && $request->password == config('app.swagger_password')) {
                return $next($request);
            } else {
                $data = ['massage' => 'username & password not valid'];
                return view('/login', $data);        
            }
        }
        return view('/login');
    }
}
