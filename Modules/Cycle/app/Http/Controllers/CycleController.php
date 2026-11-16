<?php

namespace Modules\Cycle\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Modules\Cotisation\Models\Cotisation;
use Modules\Cycle\Models\cycle;
use Modules\Cycle\Models\CycleMembers;
use Modules\Cycle\Models\Tours;

class CycleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $cycles = Cycle::all();
        return response()->json([
            $cycles
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {
        $data= $request->validate([
    'libelle' =>'required',
    'montant_cotisation' =>'required|numeric|min:1',
    'periodicite' =>'required',
    'date_debut' =>'required|date',
    'date_fin' =>'required|date|after:date_debut',
    'delai_tolerance_jours' =>'required|integer|min:0',
    'montant_penalite' =>'required|numeric|min:0',
    'frais_gestion' =>'required|numeric|min:0'

]);
        $data['statut']='brouillon';
        $data['createur_id']= Auth::id();
        cycle::create($data);
        return response()->json([
            'message'=>'cycle cree avec succes'
        ]);
    }

    /**
     * Show the specified resource.
     */
    public function show(cycle $cycle)
    {
        return response()->json([
            $cycle
        ],200);
    }

    /**
     * Update the specified resource in storage.
     */
    
public function ajoutermembres(Request $request,Cycle $cycle){
    $data = $request->validate([
        'membres' =>'required|array|min:1']);

    $dernierRang =CycleMembers::where('cycle_id',$cycle->id)->max('rang');

    if(!$dernierRang){
        $dernierRang = 0;
    }

    foreach($data['membres']as $memberId){

        CycleMembers::create([
            'cycle_id' =>$cycle->id,
            'member_id' =>$memberId,
            'rang' =>++$dernierRang
        ]);
    }

    return response()->json(['message' =>'Membres ajoutés avec succès'],201);
}

    /**
     * Remove the specified resource from storage.
     */
    public function modifierrangs(Request $request,Cycle $cycle){
    $data = $request->validate([
        'ordre' =>'required|array|min:1']);

    foreach($data['ordre'] as $position => $memberId){
        CycleMembers::where('cycle_id',$cycle->id)
        ->where('member_id',$memberId)
        ->update(['rang' =>$position + 1]);
    }
    return response()->json([
        'message' =>'Rangs modifiés avec succès'],200);
}


    public function demarrer(Cycle $cycle){
    if ($cycle->statut !== 'brouillon') {
            return response()->json([
            'message' => 'Le cycle est déjà démarré'
        ]);
    }

    $participants = CycleMembers::where('cycle_id', $cycle->id)
                        ->orderBy('rang')
                        ->get();

    if ($participants->count() == 0) {
        return response()->json([
            'message' => 'Aucun membre dans le cycle'
        ]);
    }

    foreach ($participants as $participant) {

        $tour = Tours::create([

            'cycle_id' =>$cycle->id,

            'beneficiaire_id' =>$participant->member_id,

            'numero' =>$participant->rang,

            'statut' =>$participant->rang == 1
                    ? 'ouvert'
                    : 'a_venir'

        ]);
        foreach ($participants as $cotisant){

        Cotisation::create([

            'tour_id' => $tour->id,
            'membre_id' =>$cotisant->member_id,
            'montant_attendu' =>$cycle->montant_cotisation,
            'montant_paye' =>0,
            'statut' =>'en_attente'

        ]);
        }
    }

    $cycle->update(['statut' => 'actif']);

    return response()->json(['message' =>'Cycle démarré et tours générés avec succès']);
}

    public function cloturer(Cycle $cycle){
        $cycle->update([
            'statut' =>'termine']);

        return response()->json([
            'message' =>'Cycle clôturé'
        ]);
    }
    public function destroy(cycle $cycle)
    {
        $cycle->delete();
        return response()->json([
            'message' =>'Cycle supprimé'
        ]);
    }
    public function update(Request $request, cycle $cycle)
    {
        $data= $request->validate([
            'libelle'=>'required',
            'date_debut'=>'required|date',
            'date_fin'=>'required|date|after:date_debut',
        ]);
        $cycle->update($data);
        return response()->json([
            'message'=>'cycle modifié avec succes'
        ],200);
    }
}
