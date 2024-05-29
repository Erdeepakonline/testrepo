<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UserPublisher
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
        $key = '580eca75d1ffbacca33edc3278c092e9';
        $serkey = $_SERVER['HTTP_X_API_KEY'];
        if(empty($serkey))
        {
         return response()->json('Api Key Empty');
        }
        if($serkey == $key)
        {
            return $next($request);
          
        } else {
            return response()->json('Invalid Api key');
        }
        
    }
}
