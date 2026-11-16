<?php

namespace Modules\Cycle\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Cotisation\Models\Cotisation;
use Modules\Cotisation\Models\Penalites;
use Modules\Cycle\Models\Cycle;
use Modules\Cycle\Models\CycleMembers;
use Modules\Cycle\Models\Tours;
use Modules\Members\Models\members;

class CycleController extends Controller
{
    /**
     * Tableau de bord principal (Image 02)
     */
    public function dashboard()
    {
        $cycle = Cycle::where('statut', 'actif')
            ->with(['membres', 'tours.beneficiaire', 'tours.cotisations.membre', 'tours.cotisations.penalite'])
            ->first();

        $data = [
            'cycle' => null,
        ];

        if ($cycle) {
            $tourOuvert = $cycle->tours->where('statut', 'ouvert')->first();
            $totalMembres = $cycle->membres->count();

            $encaisse = 0;
            $cotisationsEnRetard = 0;
            $montantRetard = 0;
            $retards = [];

            if ($tourOuvert) {
                foreach ($tourOuvert->cotisations as $cotisation) {
                    $encaisse += $cotisation->montant_paye;
                    if ($cotisation->statut === 'en_retard' || ($cotisation->montant_paye < $cotisation->montant_attendu && $cotisation->statut !== 'paye' && $cotisation->statut !== 'payee')) {
                        $cotisationsEnRetard++;
                        $reste = $cotisation->montant_attendu - $cotisation->montant_paye;
                        $montantRetard += $reste;
                        $membre = $cotisation->membre;
                        $penalite = $cotisation->penalite;
                        $retards[] = [
                            'membre' => $membre ? $membre->prenom . ' ' . $membre->nom : '',
                            'telephone' => $membre ? $membre->telephone : '',
                            'reste_du' => $reste,
                            'penalite' => $penalite ? $penalite->montant : 2000,
                        ];
                    }
                }
            }

            $totalAttendu = $tourOuvert ? $tourOuvert->cotisations->sum('montant_attendu') : 0;
            $membresPayes = $tourOuvert ? $tourOuvert->cotisations->whereIn('statut', ['paye', 'payee'])->count() : 0;
            $cagnotte = $encaisse - ($cycle->frais_gestion ?? 0);

            $data['cycle'] = [
                'id' => $cycle->id,
                'libelle' => $cycle->libelle,
                'tour_actuel' => $tourOuvert ? $tourOuvert->numero : 1,
                'total_tours' => $cycle->tours->count(),
                'date_echeance' => $tourOuvert ? $tourOuvert->date_prevue : null,
                'encaisse' => $encaisse,
                'total_attendu' => $totalAttendu,
                'membres_payes' => $membresPayes,
                'total_membres' => $totalMembres,
                'cotisations_en_retard' => $cotisationsEnRetard,
                'montant_retard' => $montantRetard,
                'penalites_dues' => count($retards),
                'montant_penalite' => count($retards) * ($cycle->montant_penalite ?? 2000),
                'cagnotte' => max(0, $cagnotte),
                'frais_gestion' => $cycle->frais_gestion ?? 5000,
                'beneficiaire' => $tourOuvert && $tourOuvert->beneficiaire ? [
                    'nom' => $tourOuvert->beneficiaire->prenom . ' ' . $tourOuvert->beneficiaire->nom,
                    'rang' => $tourOuvert->numero,
                ] : null,
                'retards' => $retards,
            ];
        }

        return Inertia::render('Dashboard', $data);
    }

    /**
     * Liste des cycles (Image 05)
     */
    public function index()
    {
        $cycles = Cycle::withCount('membres')->with('tours')->get()->map(function ($c) {
            $totalTours = $c->tours->count();
            $toursDistribues = $c->tours->whereIn('statut', ['ferme', 'distribue'])->count();
            $tourOuvert = $c->tours->where('statut', 'ouvert')->first();

            return [
                'id'                 => $c->id,
                'libelle'            => $c->libelle,
                'statut'             => $c->statut,
                'montant_cotisation' => $c->montant_cotisation,
                'periodicite'        => $c->periodicite,
                'date_debut'         => $c->date_debut,
                'date_fin'           => $c->date_fin,
                'membres_count'      => $c->membres_count,
                'total_tours'        => $totalTours,
                'tours_distribues'   => $toursDistribues,
                'tour_actuel'        => $tourOuvert ? $tourOuvert->numero : 1,
            ];
        });

        return Inertia::render('Cycles/Index', [
            'cycles' => $cycles
        ]);
    }

    /**
     * Formulaire nouveau cycle (Image 06)
     */
    public function create()
    {
        $membresDisponibles = members::where('statut', 'actif')->get()->map(fn($m) => [
            'id' => $m->id,
            'nom' => $m->prenom . ' ' . $m->nom,
            'telephone' => $m->telephone,
            'dans_cycle' => false,
        ]);

        return Inertia::render('Cycles/Create', [
            'membresDisponibles' => $membresDisponibles,
            'membresDuCycle' => [],
        ]);
    }

    /**
     * Enregistrer un nouveau cycle (brouillon)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'libelle'               => 'required',
            'montant_cotisation'    => 'required|numeric|min:1',
            'periodicite'           => 'required',
            'date_debut'            => 'required|date',
            'delai_tolerance_jours' => 'nullable|integer|min:0',
            'montant_penalite'      => 'nullable|numeric|min:0',
            'frais_gestion'         => 'nullable|numeric|min:0'
        ]);

        $data['statut'] = 'brouillon';
        $data['createur_id'] = Auth::id() ?? 1;

        $cycle = Cycle::create($data);

        return redirect()->route('cycles.edit', $cycle->id)->with('success', 'Cycle créé avec succès');
    }

    /**
     * Affichage du tableau matriciel du cycle (Image 07)
     */
    public function show(Cycle $cycle)
    {
        $cycle->load([
            'membres' => fn ($q) => $q->orderBy('cycle_member.rang'),
            'tours' => fn ($q) => $q->orderBy('numero'),
            'tours.beneficiaire',
            'tours.cotisations.membre',
            'tours.cotisations.paiements',
            'tours.cotisations.penalite',
        ]);

        $membres = $cycle->membres->map(fn ($m) => [
            'id'   => $m->id,
            'nom'  => $m->prenom . ' ' . $m->nom,
            'rang' => $m->pivot->rang,
        ]);

        $tours = $cycle->tours->map(function ($tour) {
            return [
                'id'            => $tour->id,
                'numero'        => $tour->numero,
                'date_prevue'   => $tour->date_prevue,
                'statut'        => $tour->statut,
                'beneficiaire'  => $tour->beneficiaire ? [
                    'id'  => $tour->beneficiaire->id,
                    'nom' => $tour->beneficiaire->prenom . ' ' . $tour->beneficiaire->nom,
                ] : null,
                'cotisations'   => $tour->cotisations->map(fn ($c) => [
                    'id'              => $c->id,
                    'membre_id'       => $c->membre_id,
                    'montant_attendu' => $c->montant_attendu,
                    'montant_paye'    => $c->montant_paye,
                    'statut'          => $c->statut,
                ]),
            ];
        });

        $tourOuvert = $cycle->tours->where('statut', 'ouvert')->first();

        return Inertia::render('Cycles/Tableau', [
            'cycle' => [
                'id'                 => $cycle->id,
                'libelle'            => $cycle->libelle,
                'statut'             => $cycle->statut,
                'montant_cotisation' => $cycle->montant_cotisation,
                'periodicite'        => $cycle->periodicite,
                'date_debut'         => $cycle->date_debut,
            ],
            'membres'    => $membres,
            'tours'      => $tours,
            'tourOuvert' => $tourOuvert ? [
                'id' => $tourOuvert->id,
                'numero' => $tourOuvert->numero,
            ] : null,
        ]);
    }

    /**
     * Éditer le cycle (mode brouillon)
     */
    public function edit(Cycle $cycle)
    {
        $cycle->load(['membres' => function ($q) {
            $q->orderBy('cycle_member.rang');
        }]);

        $membresDuCycle = $cycle->membres->map(fn ($m) => [
            'id'        => $m->id,
            'nom'       => $m->prenom . ' ' . $m->nom,
            'telephone' => $m->telephone,
            'rang'      => $m->pivot->rang,
        ]);

        $membresIds = $cycle->membres->pluck('id')->toArray();

        $membresDisponibles = members::where('statut', 'actif')
            ->get()
            ->map(fn ($m) => [
                'id'         => $m->id,
                'nom'        => $m->prenom . ' ' . $m->nom,
                'telephone'  => $m->telephone,
                'dans_cycle' => in_array($m->id, $membresIds),
            ]);

        return Inertia::render('Cycles/Create', [
            'cycle'              => $cycle,
            'membresDuCycle'     => $membresDuCycle,
            'membresDisponibles' => $membresDisponibles,
        ]);
    }

    /**
     * Ajouter des membres à un cycle
     */
    public function ajoutermembres(Request $request, Cycle $cycle)
    {
        $data = $request->validate([
            'membres' => 'required|array|min:1'
        ]);

        $dernierRang = CycleMembers::where('cycle_id', $cycle->id)->max('rang') ?? 0;

        foreach ($data['membres'] as $memberId) {
            $exists = CycleMembers::where('cycle_id', $cycle->id)->where('member_id', $memberId)->exists();
            if (!$exists) {
                CycleMembers::create([
                    'cycle_id'  => $cycle->id,
                    'member_id' => $memberId,
                    'rang'      => ++$dernierRang
                ]);
            }
        }

        return redirect()->back()->with('success', 'Membres ajoutés avec succès');
    }

    /**
     * Modifier les rangs des membres du cycle
     */
    public function modifierrangs(Request $request, Cycle $cycle)
    {
        $data = $request->validate([
            'ordre' => 'required|array|min:1'
        ]);

        foreach ($data['ordre'] as $position => $memberId) {
            CycleMembers::where('cycle_id', $cycle->id)
                ->where('member_id', $memberId)
                ->update(['rang' => $position + 1]);
        }

        return redirect()->back()->with('success', 'Rangs modifiés avec succès');
    }

    /**
     * Démarrer le cycle
     */
    public function demarrer(Cycle $cycle)
    {
        if ($cycle->statut !== 'brouillon') {
            return redirect()->back()->with('error', 'Le cycle est déjà démarré');
        }

        $participants = CycleMembers::where('cycle_id', $cycle->id)
            ->orderBy('rang')
            ->get();

        if ($participants->count() == 0) {
            return redirect()->back()->with('error', 'Aucun membre dans le cycle');
        }

        foreach ($participants as $participant) {
            $tour = Tours::create([
                'cycle_id'        => $cycle->id,
                'beneficiaire_id' => $participant->member_id,
                'numero'          => $participant->rang,
                'statut'          => $participant->rang == 1 ? 'ouvert' : 'a_venir'
            ]);

            foreach ($participants as $cotisant) {
                Cotisation::create([
                    'tour_id'         => $tour->id,
                    'membre_id'       => $cotisant->member_id,
                    'montant_attendu' => $cycle->montant_cotisation,
                    'montant_paye'    => 0,
                    'statut'          => 'en_attente'
                ]);
            }
        }

        $cycle->update(['statut' => 'actif']);

        return redirect()->route('cycles.show', $cycle->id)->with('success', 'Cycle démarré et tours générés avec succès');
    }

    /**
     * Clôturer le cycle
     */
    public function cloturer(Cycle $cycle)
    {
        $cycle->update(['statut' => 'termine']);

        return redirect()->back()->with('success', 'Cycle clôturé');
    }

    /**
     * Supprimer un cycle
     */
    public function destroy(Cycle $cycle)
    {
        $cycle->delete();

        return redirect()->route('cycles.index')->with('success', 'Cycle supprimé');
    }

    /**
     * Mettre à jour les informations du cycle
     */
    public function update(Request $request, Cycle $cycle)
    {
        $data = $request->validate([
            'libelle'    => 'required',
            'date_debut' => 'required|date',
        ]);

        $cycle->update($data);

        return redirect()->back()->with('success', 'Cycle modifié avec succès');
    }
}
