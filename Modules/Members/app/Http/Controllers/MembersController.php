<?php

namespace Modules\Members\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Members\Models\members;

class MembersController extends Controller
{
    /**
     * Display a listing of the resource.
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
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Membres/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nom'           => 'required',
            'prenom'        => 'required',
            'email'         => 'required|nullable|email|unique:members',
            'telephone'     => 'required|unique:members',
            'adresse'       => 'required',
            'date_adhesion' => 'required|date',
            'statut'        => 'required|in:actif,sorti,desactive'
        ]);

        members::create($data);

        return redirect()->route('members.index')->with('success', 'Membre créé avec succès.');
    }

    /**
     * Show the specified resource.
     */
    public function show(members $member)
    {
        return Inertia::render('Membres/Create', [
            'member' => $member
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(members $member)
    {
        return Inertia::render('Membres/Create', [
            'member' => $member
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, members $member)
    {
        $data = $request->validate([
            'nom'           => 'required',
            'prenom'        => 'required',
            'telephone'     => 'required|unique:members,telephone,' . $member->id,
            'email'         => 'required|nullable|email|unique:members,email,' . $member->id,
            'adresse'       => 'required',
            'date_adhesion' => 'required|date',
            'statut'        => 'required|in:actif,sorti,desactive'
        ]);

        $member->update($data);

        return redirect()->route('members.index')->with('success', 'Membre modifié avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(members $member)
    {
        $member->delete();

        return redirect()->route('members.index')->with('success', 'Membre supprimé avec succès.');
    }
}
