<?php

namespace App\Http\Controllers;

use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Modules\Members\Models\Members;

class UserController extends Controller
{
    public function index()
    {
        $users = Users::all()->map(fn ($u) => [
            'id'    => $u->id,
            'name'  => $u->name,
            'email' => $u->email,
            'role'  => $u->role,
        ]);

        $membres = Members::where('statut', 'actif')->get()->map(fn ($m) => [
            'id'  => $m->id,
            'nom' => $m->prenom . ' ' . $m->nom,
        ]);

        return Inertia::render('Utilisateurs/Index', [
            'users'   => $users,
            'membres' => $membres,
        ]);
    }

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

        return redirect()->route('utilisateurs.index')->with('success', 'Compte créé.');
    }
}
