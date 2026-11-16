<?php

namespace Modules\Cycle\Http\Controllers;

use App\Http\Controllers\Controller;
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
        $tour->update(['statut' => 'distribue']);

        return redirect()->back()->with('success', 'Tour distribué avec succès');
    }
}
