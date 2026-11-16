<?php

namespace Modules\Cotisation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Cotisation\Models\Penalites;

class PenaliteController extends Controller
{
    public function store(Request $request){
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

        $penalite->update(['statut' =>'payee']);

        return response()->json(['message' =>'Pénalité payée']);
    }
}