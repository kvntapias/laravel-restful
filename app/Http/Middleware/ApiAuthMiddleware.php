<?php

namespace App\Http\Middleware;

use Closure;
use App\Helpers\JWTAuth;

class ApiAuthMiddleware
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
        $token = $request->header('Authorization');
        $jwtAuth = new JWTAuth();
        $checktoken = $jwtAuth->checkToken($token);
        if ($checktoken) {            
            return $next($request);
        }else{
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'EL usuario no está identificado'
            );
            return response()->json($data, $data['code']);
        }
    }
}
