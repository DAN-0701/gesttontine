<?php

namespace App\Http\Controllers;

use App\Models\Users;
use App\Notifications\TempPasswordNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PasswordResetController extends Controller
{
    /**
     * Traiter la demande de mot de passe oublié
     * - Génère un mot de passe temporaire aléatoire
     * - Hash et sauvegarde dans users
     * - force_password_change = true
     * - Envoie la notification par email
     * - Message générique systématique (ne jamais afficher le mot de passe)
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = trim(strtolower($request->input('email')));
        $user = Users::where('email', $email)->first();

        if ($user) {
            // Générer mot de passe temporaire sécurisé et aléatoire de 10 caractères
            $tempPassword = Str::password(10, letters: true, numbers: true, symbols: false, spaces: false);

            $user->update([
                'password'              => Hash::make($tempPassword),
                'force_password_change' => true,
            ]);

            try {
                $user->notify(new TempPasswordNotification($tempPassword));
            } catch (\Throwable $e) {
                // Log l'erreur si besoin sans interrompre
                \Illuminate\Support\Facades\Log::info("Mot de passe temporaire pour {$email} : {$tempPassword}");
            }
        }

        // Message générique de sécurité imposé
        return response()->json([
            'success' => true,
            'message' => 'Si cette adresse e-mail existe dans notre système, un mot de passe temporaire a été envoyé.'
        ]);
    }

    /**
     * Page de changement de mot de passe obligatoire
     */
    public function showChangeRequired()
    {
        return Inertia::render('Auth/ChangeRequiredPassword');
    }

    /**
     * Enregistrer le nouveau mot de passe choisi par l'utilisateur
     */
    public function updateChangeRequired(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $user->update([
            'password'              => Hash::make($request->input('password')),
            'force_password_change' => false,
        ]);

        return redirect()->route('dashboard')->with('success', 'Votre mot de passe a été mis à jour avec succès.');
    }
}
