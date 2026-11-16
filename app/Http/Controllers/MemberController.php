<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Members\Models\Members;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $query = Members::query();

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

    public function create()
    {
        return Inertia::render('Membres/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom'            => 'required|string',
            'prenom'         => 'required|string',
            'telephone'      => 'required|string|unique:members,telephone',
            'email'          => 'nullable|email|unique:members,email',
            'adresse'        => 'nullable|string',
            'date_adhesion'  => 'required|date',
            'statut'         => 'required|in:actif,sorti',
        ]);

        Members::create($data);

        return redirect()->route('membres.index')->with('success', 'Membre créé avec succès.');
    }

    public function edit(Members $member)
    {
        return Inertia::render('Membres/Create', [
            'member' => $member,
        ]);
    }

    public function update(Request $request, Members $member)
    {
        $data = $request->validate([
            'nom'            => 'required|string',
            'prenom'         => 'required|string',
            'telephone'      => 'required|string|unique:members,telephone,' . $member->id,
            'email'          => 'nullable|email|unique:members,email,' . $member->id,
            'adresse'        => 'nullable|string',
            'date_adhesion'  => 'required|date',
            'statut'         => 'required|in:actif,sorti',
        ]);

        $member->update($data);

        return redirect()->route('membres.index')->with('success', 'Membre modifié avec succès.');
    }
}
