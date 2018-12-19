<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\UserPin;

class CheckUserStatus
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
        if(Auth::check() && Auth::user() && Auth::user()->status !=1){
            $user_email = Auth::user()->email;

            UserPin::delete_row(Auth::user()->id);

            Auth::user()->AauthAcessToken()->delete();

            return \Response::json(['type'=>'error',
                'message' => 'Unverified user',
                'data' => [
                    'email' => $user_email
                ]
            ], 400);
        }

        return $next($request);
    }
}
