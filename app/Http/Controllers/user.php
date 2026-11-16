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
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|string|min:6',
            'role'      => 'required|in:gestionnaire,tresorier',
            'member_id' => 'nullable|integer',
        ]);

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

        return response()->json([
            'message' => 'connexion reussi',
            'user'    => Auth::user()
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
}
