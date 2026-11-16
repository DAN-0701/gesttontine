<?php

namespace Modules\Members\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Members\Models\members;

class MembersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(){
        return response()->json($member=members::all(),200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {
        $data =$request->validate([
            'nom'=>'required',
            'prenom'=>'required',
            'email'=>'required|nullable|email|unique:members',
            'telephone'=>'required|unique:members',
            'adresse'=> 'required',
            'date_adhesion'=>'required|date',
            'statut'=>'required|in:actif,desactive'
        ]);
        members::create($data);
        return response()->json([
            'message'=>'membre cree avec succes'
        ],200) ;
    }

    /**
     * Show the specified resource.
     */
    public function show(members $member){
        $data=members::where('id',$member->id)->first();
        
        return response()->json([
            'nom'=>$data->nom,
            'prenom'=>$data->prenom,
            'email'=>$data->email,
            'telephone'=>$data->telephone,
            'adresse'=>$data->adresse,
            'date_adhesion'=>$data->date_adhesion,
            'statut'=>$data->statut
        ],200);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, members $member) {
        $data = $request->validate([
            'nom'=>'required',
            'prenom'=>'required',
            'telephone'=>'required|unique:members,telephone,'.$member->id,
            'email'=>'required|nullable|email|unique:members,email,' .$member->id,
            'adresse'=> 'required',
            'date_adhesion'=>'required|date',
            'statut'=>'required|in:actif,desactive'
        ]);
        $member->update($data);
        return response()->json(
            $member
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(members $member) {
        $member->delete();
        return response ()->
            json([
                'message'=>'membres supprime'
            ]);
    }
}
