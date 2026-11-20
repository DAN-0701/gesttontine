<?php

namespace App\Http\Controllers;

use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Modules\Members\Models\members;

class user extends Controller
{
    /**
     * Affichage de la liste des utilisateurs (Image 10)
     */
    public function index()
    {
        $authUser = Auth::user();
        if ($authUser && method_exists($authUser, 'hasPermission')) {
            if (!$authUser->hasPermission('creer_utilisateur') && $authUser->role !== 'administrateur') {
                abort(403, "Action non autorisée : permission 'creer_utilisateur' requise.");
            }
        }

        $users = Users::all()->map(fn ($u) => [
            'id'    => $u->id,
            'name'  => $u->name,
            'email' => $u->email,
            'role'  => $u->role,
        ]);

        $membres = members::where('statut', 'actif')->get()->map(fn ($m) => [
            'id'  => $m->id,
            'nom' => $m->prenom . ' ' . $m->nom,
        ]);

        return Inertia::render('Utilisateurs/Index', [
            'users'   => $users,
            'membres' => $membres,
        ]);
    }

    /**
     * Création d'un nouvel utilisateur
     */
    public function store(Request $request){
        $authUser = Auth::user();
        if ($authUser && method_exists($authUser, 'hasPermission')) {
            if (!$authUser->hasPermission('creer_utilisateur')) {
                abort(403, "Action non autorisée : permission 'creer_utilisateur' requise.");
            }
        }

        $data = $request->validate([
            'name'      => 'required|string',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|string|min:8',
            'role'      => 'required|in:administrateur,gestionnaire,tresorier',
            'member_id' => 'nullable|integer',
        ]);

        // Seul un administrateur peut créer un autre administrateur
        if ($data['role'] === 'administrateur' && (!$authUser || $authUser->role !== 'administrateur')) {
            abort(403, 'Seul un administrateur peut créer un compte avec le rôle Administrateur.');
        }

        Users::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role'     => $data['role'],
        ]);

        return redirect()->back()->with('success', 'Utilisateur créé avec succès.');
    }

    /**
     * Connexion utilisateur
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($data)) {
            return response()->json([
                'message' => 'identifiants incorrect'
            ], 401);
        }

        $request->session()->regenerate();

        $authUser = Auth::user();

        return response()->json([
            'message'               => 'connexion reussi',
            'user'                  => $authUser,
            'force_password_change' => (bool) $authUser->force_password_change,
            'redirect'              => $authUser->force_password_change ? '/mot-de-passe-obligatoire' : '/dashboard'
        ]);
    }

    /**
     * Déconnexion utilisateur
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();

        return response()->json([
            'message' => 'deconnexion reussi'
        ]);
    }

    /**
     * Modification du mot de passe
     */
    public function update(Request $request, Users $user)
    {
        $data = $request->validate([
            'email'    => 'required|email|exists:users,email',
            'password' => 'required|min:8'
        ]);

        $user = Users::where('email', $data['email'])->firstOrFail();
        $user->update([
            'password' => Hash::make($data['password'])
        ]);

        return response()->json([
            'id'       => $user->id,
            'message'  => 'mot de passe modifié avec succès',
            'password' => $data['password']
        ]);
    }
    /**
     * Modification du profil utilisateur connecté
     * (Exécute updateall puis déconnecte l'utilisateur et le renvoie sur "/")
     */
    public function updateall(Request $request, Users $user)
    {
        // Seul l'utilisateur lui-même ou un administrateur peut modifier ses infos
        $authUser = Auth::user();
        if (!$authUser || ($authUser->id !== $user->id && $authUser->role !== 'administrateur')) {
            abort(403, 'Action non autorisée.');
        }

        $data = $request->validate([
            'name'      => 'required|string',
            'email'     => 'required|email|unique:users,email,' . $user->id,
            'password'  => 'nullable|string|min:8',
        ]);

        $updateData = [
            'name'  => $data['name'],
            'email' => $data['email'],
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);

        // Déconnexion automatique de l'utilisateur après modification de ses infos
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->wantsJson()) {
            return response()->json([
                'message'  => 'Profil mis à jour avec succès. Veuillez vous reconnecter.',
                'redirect' => '/'
            ]);
        }

        return redirect('/')->with('success', 'Profil mis à jour avec succès. Veuillez vous reconnecter.');
    }
}
