<?php

namespace Modules\Cotisation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Cotisation\Models\Penalites;

class PenaliteController extends Controller
{
    private function checkPermission(string $permission)
    {
        $user = Auth::user();
        if ($user && method_exists($user, 'hasPermission')) {
            if (!$user->hasPermission($permission)) {
                abort(403, "Action non autorisée : permission '{$permission}' requise.");
            }
        }
    }

    public function store(Request $request){
        $this->checkPermission('enregistrer_paiement');

        $data =$request->validate([
                'cotisation_id' =>'required',
                'montant' =>'required|numeric'
            ]);

        Penalites::create([

            'cotisation_id' => $data['cotisation_id'],

            'montant' => $data['montant'],

            'statut' => 'due',

            'traite_par' => Auth::id()

        ]);

        return response()->json(['message' =>'Pénalité créée']);
    }

    public function payer(Penalites $penalite){
        $this->checkPermission('payer_penalite');

        $penalite->update([
            'statut'         => 'payee',
            'date_paiement'  => now()->toDateString(),
        ]);

        return redirect()->back()->with('success', 'Pénalité payée avec succès.');
    }

    /**
     * Annuler une pénalité avec motif d'annulation (Demande 7)
     */
    public function annuler(Request $request, Penalites $penalite)
    {
        $this->checkPermission('annuler_penalite');

        $data = $request->validate([
            'motif_annulation' => 'required|string|min:3',
        ]);

        $penalite->update([
            'statut'           => 'annulee',
            'motif_annulation' => $data['motif_annulation'],
            'traite_par'       => Auth::id() ?? 1,
        ]);

        return redirect()->back()->with('success', 'Pénalité annulée avec succès.');
    }
}