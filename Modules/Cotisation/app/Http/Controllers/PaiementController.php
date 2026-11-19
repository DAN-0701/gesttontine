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
        $user = Auth::user();
        if ($user && method_exists($user, 'hasPermission')) {
            if (!$user->hasPermission('enregistrer_paiement')) {
                abort(403, "Action non autorisée : permission 'enregistrer_paiement' requise.");
            }
        }

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
        // Vérification de la permission enregistrer_paiement
        $user = Auth::user();
        if ($user && method_exists($user, 'hasPermission')) {
            if (!$user->hasPermission('enregistrer_paiement')) {
                if ($request->wantsJson()) {
                    return response()->json(['error' => "Action non autorisée : permission 'enregistrer_paiement' requise."], 403);
                }
                abort(403, "Action non autorisée : permission 'enregistrer_paiement' requise.");
            }
        }

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

    /**
     * Annuler un paiement (Demande 7)
     * Le gestionnaire doit fournir un motif d'annulation.
     * La cotisation recalcule son montant_paye et revient au statut adéquat :
     * 'en_attente' ou 'partielle' si pas encore dépassée, ou 'en_retard' si date_prevue + tolérance est dépassée.
     */
    public function annuler(Request $request, Paiements $paiement)
    {
        $user = Auth::user();
        if ($user && method_exists($user, 'hasPermission')) {
            if (!$user->hasPermission('annuler_paiement')) {
                abort(403, "Action non autorisée : permission 'annuler_paiement' requise.");
            }
        } elseif ($user && $user->role === 'tresorier') {
            abort(403, 'Seul le gestionnaire peut annuler un paiement.');
        }

        $data = $request->validate([
            'motif_annulation' => 'required|string|min:3',
        ]);

        if ($paiement->annule) {
            return redirect()->back()->with('error', 'Ce paiement est déjà annulé.');
        }

        $paiement->update([
            'annule'           => true,
            'motif_annulation' => $data['motif_annulation'],
        ]);

        // Déduire le montant annulé de la cotisation
        $cotisation = $paiement->cotisation;
        if ($cotisation) {
            $cotisation->montant_paye = max(0, $cotisation->montant_paye - $paiement->montant);

            // Charger le tour et le cycle pour vérifier la date d'échéance + tolérance
            $cotisation->load('tour.cycle');
            $tour = $cotisation->tour;
            $cycle = $tour ? $tour->cycle : null;

            $isRetardApplicable = false;
            if ($tour && $tour->date_prevue && $cycle) {
                $tolerance = $cycle->delai_tolerance_jours ?? 0;
                $limite = \Carbon\Carbon::parse($tour->date_prevue)->addDays($tolerance);
                $isRetardApplicable = \Carbon\Carbon::today()->greaterThan($limite);
            }

            if ($cotisation->montant_paye >= $cotisation->montant_attendu) {
                $cotisation->statut = 'payee';
            } elseif ($cotisation->montant_paye > 0) {
                $cotisation->statut = 'partielle';
            } else {
                $cotisation->statut = $isRetardApplicable ? 'en_retard' : 'en_attente';
            }

            $cotisation->save();
        }

        return redirect()->back()->with('success', 'Paiement annulé avec succès.');
    }
}
