<?php

namespace Modules\Cotisation\Http\Controllers;

use App\Http\Controllers\Controller;

use Modules\Cotisation\Models\Cotisation;
use Modules\Cycle\Models\Tour;
use Modules\Cycle\Models\Tours;

class CotisationController extends Controller{

    public function index(){
        $cotisations =Cotisation::all();

        return response()->json($cotisations);
    }

    public function show(Cotisation $cotisation){

        return response()->json($cotisation);
    }

    public function tour(Tours $tour){

        $cotisations =Cotisation::where('tour_id',$tour->id)
                                ->get();

        return response()->json($cotisations);
    }
}