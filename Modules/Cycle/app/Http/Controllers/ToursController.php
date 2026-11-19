<?php

namespace Modules\Cycle\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Cycle\Models\Cycle;
use Modules\Cycle\Models\Tours;

class ToursController extends Controller
{
    public function index()
    {
        $tours = Tours::all();
        return response()->json($tours, 200);
    }

    public function show(Tours $tour)
    {
        return response()->json($tour, 200);
    }

    public function toursCycle(Cycle $cycle)
    {
        $tours = Tours::where('cycle_id', $cycle->id)
            ->orderBy('numero')
            ->get();

        return response()->json($tours, 200);
    }

    public function distribuer(Tours $tour)
    {
        $user = Auth::user();
        if ($user && method_exists($user, 'hasPermission')) {
            if (!$user->hasPermission('cloturer_tour')) {
                abort(403, "Action non autorisée : permission 'cloturer_tour' requise.");
            }
        }

        $tour->update(['statut' => 'distribue']);

        return redirect()->back()->with('success', 'Tour distribué avec succès');
    }
}
