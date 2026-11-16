<?php

namespace App\Http\Controllers;

use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class user extends Controller{
    
    public function login(Request $request){
        $data=$request->validate([
            'email'=>'required|email',
            'password'=>'required',
        ]);
        if(! Auth::attempt($data)){
            return response()->json([
                'message'=>'identifiants incorrect'
            ],401);
        }
        $request->session()->regenerate();
        return response()->json([
            'message'=>'connexion reussi',
            'user'=>Auth::user()
        ]);
    }

    public function logout(Request $request){
        Auth::logout();
        $request->session()->invalidate();
        return response()->json([
            'message'=>'deconnexion reussi'
            ]);
    }

    public function update(Request $request, Users $user){
        $data=$request->validate([
            'email'    => 'required|email|exists:users,email',
            'password'=>'required|min:8'
        ]);
        $user = Users::where('email', $data['email'])->firstOrFail();
        $user->update([
            'password'=>Hash::make($data['password'])
        ]);
        return response()->json([
            'id'=>$user->id,
            'message'=>'mot de passe modifié avec succès',
            'password' => $data['password']
        ]);
    }
            

        }
        
