<?php

namespace Modules\Members\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Modules\Members\Models\members;

class MembersController extends Controller
{
    private function checkGestionnaire()
    {
        $user = Auth::user();
        if ($user && $user->role === 'tresorier') {
            abort(403, 'Action non autorisée pour le trésorier.');
        }
    }

    /**
     * Liste des membres (Image 03)
     */
    public function index(Request $request)
    {
        $query = members::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%")
                  ->orWhere('telephone', 'like', "%{$search}%");
            });
        }

        if ($statut = $request->input('statut')) {
            if ($statut !== 'tous') {
                $query->where('statut', $statut);
            }
        }

        $membres = $query->get();

        return Inertia::render('Membres/Index', [
            'membres' => $membres,
            'filters' => [
                'search' => $search,
                'statut' => $statut ?? 'tous',
            ],
        ]);
    }

    /**
     * Formulaire création d'un membre (Image 04)
     */
    public function create()
    {
        $this->checkGestionnaire();

        return Inertia::render('Membres/Create');
    }

    /**
     * Enregistrer un nouveau membre
     */
    public function store(Request $request)
    {
        $this->checkGestionnaire();

        $data = $request->validate([
            'nom'           => 'required|string',
            'prenom'        => 'required|string',
            'email'         => 'nullable|email|unique:members,email',
            'telephone'     => 'required|unique:members,telephone',
            'adresse'       => 'required|string',
            'date_adhesion' => 'required|date',
            'statut'        => 'required|in:actif,desactive'
        ]);

        members::create($data);

        return redirect()->route('members.index')->with('success', 'Membre créé avec succès.');
    }

    /**
     * Afficher ou éditer un membre avec les anciennes valeurs pré-remplies
     */
    public function edit(members $member)
    {
        $this->checkGestionnaire();

        return Inertia::render('Membres/Edit', [
            'member' => $member
        ]);
    }

    public function show(members $member)
    {
        return $this->edit($member);
    }

    /**
     * Mettre à jour un membre
     */
    public function update(Request $request, members $member)
    {
        $this->checkGestionnaire();

        $data = $request->validate([
            'nom'           => 'required|string',
            'prenom'        => 'required|string',
            'telephone'     => 'required|unique:members,telephone,' . $member->id,
            'email'         => 'nullable|email|unique:members,email,' . $member->id,
            'adresse'       => 'required|string',
            'date_adhesion' => 'required|date',
            'statut'        => 'required|in:actif,desactive'
        ]);

        $member->update($data);

        return redirect()->route('members.index')->with('success', 'Membre modifié avec succès.');
    }

    /**
     * Supprimer un membre
     */
    public function destroy(members $member)
    {
        $this->checkGestionnaire();

        $member->delete();

        return redirect()->route('members.index')->with('success', 'Membre supprimé avec succès.');
    }
}
