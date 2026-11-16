<?php

namespace Modules\Cotisation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Cotisation\Models\Cotisation;
use Modules\Cotisation\Models\Paiements;

class PaiementController extends Controller{

    public function store(Request $request){

        $data =$request->validate([
                'cotisation_id' =>'required',
                'montant' =>'required|numeric|min:1'
]);

        $cotisation =Cotisation::findOrFail(
                $data['cotisation_id']);

        Paiements::create([

            'cotisation_id' =>$data['cotisation_id'],

            'montant' =>$data['montant'],

            'enregistre_par' =>Auth::id()

        ]);

        $cotisation->montant_paye +=$data['montant'];

        if($cotisation->montant_paye>=$cotisation->montant_attendu){
            $cotisation->statut ='payee';
        }
        else{
            $cotisation->statut ='partielle';
        }

        $cotisation->save();

        return response()->json(['message' =>'Paiement enregistré']);
    }
}