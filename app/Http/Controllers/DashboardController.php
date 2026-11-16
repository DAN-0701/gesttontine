<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Cycle\Models\Cycle;
use Modules\Cycle\Models\Tours;
use Modules\Cotisation\Models\Cotisation;
use Modules\Cotisation\Models\Penalites;

class DashboardController extends Controller
{
    public function index()
    {
        // Find the active cycle
        $cycle = Cycle::where('statut', 'actif')->with(['membres', 'tours.beneficiaire', 'tours.cotisations.membre'])->first();

        $data = [
            'cycle' => null,
        ];

        if ($cycle) {
            $tourOuvert = $cycle->tours->where('statut', 'ouvert')->first();
            $totalMembres = $cycle->membres->count();

            // Calculate stats for the current open tour
            $encaisse = 0;
            $cotisationsEnRetard = 0;
            $montantRetard = 0;
            $penalitesDues = 0;
            $montantPenalite = 0;
            $retards = [];

            if ($tourOuvert) {
                foreach ($tourOuvert->cotisations as $cotisation) {
                    $encaisse += $cotisation->montant_paye;
                    if ($cotisation->statut === 'en_retard') {
                        $cotisationsEnRetard++;
                        $montantRetard += ($cotisation->montant_attendu - $cotisation->montant_paye);
                        $membre = $cotisation->membre;
                        $penalite = $cotisation->penalite;
                        $retards[] = [
                            'membre' => $membre ? $membre->prenom . ' ' . $membre->nom : '',
                            'telephone' => $membre ? $membre->telephone : '',
                            'reste_du' => $cotisation->montant_attendu - $cotisation->montant_paye,
                            'penalite' => $penalite ? $penalite->montant : 0,
                            'penalite_statut' => $penalite ? $penalite->statut : null,
                        ];
                    }
                }

                $penalites = Penalites::whereHas('cotisation', function ($q) use ($tourOuvert) {
                    $q->where('tour_id', $tourOuvert->id);
                })->get();

                $penalitesDues = $penalites->count();
                $montantPenalite = $penalites->sum('montant');
            }

            $totalAttendu = $tourOuvert ? $tourOuvert->cotisations->sum('montant_attendu') : 0;
            $membresPayes = $tourOuvert ? $tourOuvert->cotisations->where('statut', 'paye')->count() : 0;
            $cagnotte = $encaisse - ($cycle->frais_gestion ?? 0);

            $beneficiaire = $tourOuvert && $tourOuvert->beneficiaire ? [
                'nom' => $tourOuvert->beneficiaire->prenom . ' ' . $tourOuvert->beneficiaire->nom,
                'rang' => $tourOuvert->numero,
            ] : null;

            $tourActuel = $tourOuvert ? $tourOuvert->numero : null;
            $totalTours = $cycle->tours->count();

            $data['cycle'] = [
                'id' => $cycle->id,
                'libelle' => $cycle->libelle,
                'tour_actuel' => $tourActuel,
                'total_tours' => $totalTours,
                'date_echeance' => $tourOuvert ? $tourOuvert->date_prevue : null,
                'encaisse' => $encaisse,
                'total_attendu' => $totalAttendu,
                'membres_payes' => $membresPayes,
                'total_membres' => $totalMembres,
                'cotisations_en_retard' => $cotisationsEnRetard,
                'montant_retard' => $montantRetard,
                'penalites_dues' => $penalitesDues,
                'montant_penalite' => $montantPenalite,
                'cagnotte' => $cagnotte,
                'frais_gestion' => $cycle->frais_gestion ?? 0,
                'montant_cotisation' => $cycle->montant_cotisation,
                'beneficiaire' => $beneficiaire,
                'retards' => $retards,
            ];
        }

        return Inertia::render('Dashboard', $data);
    }
}
