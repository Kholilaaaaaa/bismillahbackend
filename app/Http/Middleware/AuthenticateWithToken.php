<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Pengguna;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateWithToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('Authorization');
        
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak ditemukan'
            ], 401);
        }

        $token = str_replace('Bearer ', '', $token);
        
        $pengguna = Pengguna::validateAuthToken($token);
        
        if (!$pengguna) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid'
            ], 401);
        }

        // Attach pengguna to request
        $request->merge(['pengguna' => $pengguna]);
        $request->setUserResolver(function () use ($pengguna) {
            return $pengguna;
        });

        return $next($request);
    }
}