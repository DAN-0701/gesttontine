<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->force_password_change) {
            // Permettre uniquement l'accès à la page de changement obligatoire, la route de soumission et la déconnexion
            $allowedRoutes = [
                'password.change-required',
                'password.change-required.update',
                'logout',
            ];

            $currentRoute = $request->route() ? $request->route()->getName() : null;

            if (!in_array($currentRoute, $allowedRoutes) && !$request->is('mot-de-passe-obligatoire*') && !$request->is('logout')) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'force_password_change' => true,
                        'message' => 'Veuillez changer votre mot de passe temporaire pour continuer.',
                        'redirect' => '/mot-de-passe-obligatoire'
                    ], 403);
                }
                return redirect()->route('password.change-required');
            }
        }

        return $next($request);
    }
}
