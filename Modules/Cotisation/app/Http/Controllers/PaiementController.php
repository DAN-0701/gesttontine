<?php

namespace Modules\Cotisation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Modules\Cotisation\Models\Cotisation;
use Modules\Cotisation\Models\Paiements;

class PaiementController extends Controller
{
    /**
     * Afficher le formulaire de paiement (Image 08)
     */
    public function create(Cotisation $cotisation)
    {
        $cotisation->load(['membre', 'tour.cycle', 'paiements']);

        return Inertia::render('Paiements/Create', [
            'cotisation' => [
                'id'              => $cotisation->id,
                'montant_attendu' => $cotisation->montant_attendu,
                'montant_paye'    => $cotisation->montant_paye,
                'reste_du'        => $cotisation->montant_attendu - $cotisation->montant_paye,
                'membre'          => $cotisation->membre ? $cotisation->membre->prenom . ' ' . $cotisation->membre->nom : '',
                'tour_numero'     => $cotisation->tour ? $cotisation->tour->numero : null,
                'date_echeance'   => $cotisation->tour ? $cotisation->tour->date_prevue : null,
                'paiements'       => $cotisation->paiements,
            ],
        ]);
    }

    /**
     * Enregistrer un paiement
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'cotisation_id' => 'required',
            'montant'       => 'required|numeric|min:1',
            'mode'          => 'nullable|string',
            'date_paiement' => 'nullable|date',
            'reference'     => 'nullable|string',
        ]);

        $cotisation = Cotisation::findOrFail($data['cotisation_id']);

        Paiements::create([
            'cotisation_id'  => $data['cotisation_id'],
            'montant'        => $data['montant'],
            'date_paiement'  => $data['date_paiement'] ?? now()->toDateString(),
            'mode'           => $data['mode'] ?? 'especes',
            'reference'      => $data['reference'] ?? null,
            'annule'         => false,
            'enregistre_par' => Auth::id() ?? 1,
        ]);

        $cotisation->montant_paye += $data['montant'];

        if ($cotisation->montant_paye >= $cotisation->montant_attendu) {
            $cotisation->statut = 'payee';
        } else {
            $cotisation->statut = 'partielle';
        }

        $cotisation->save();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Paiement enregistré']);
        }

        return redirect()->back()->with('success', 'Paiement enregistré avec succès.');
    }
}
