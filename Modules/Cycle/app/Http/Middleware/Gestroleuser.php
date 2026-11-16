<?php

namespace Modules\Cycle\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class gestroleuser
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if(!Auth::check()){
            return response()->json([
                'message'=>'vous devez etre connecter pour acceder a cette ressource'
            ],401);
        }
        if(Auth::user()->role !== 'gestionnaire'){
            return response()->json([
                'message'=>'vous n\'avez pas les droits pour acceder a cette ressource'
            ],403);
        }

        return $next($request);
    }
}
