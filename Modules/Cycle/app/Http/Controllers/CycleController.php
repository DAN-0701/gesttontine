<?php

namespace Modules\Cycle\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Modules\Cotisation\Models\Cotisation;
use Modules\Cycle\Models\Cycle;
use Modules\Cycle\Models\CycleMembers;
use Modules\Cycle\Models\Tours;
use Modules\Members\Models\members;

class CycleController extends Controller
{
    /**
     * Vérifier si l'utilisateur connecté est gestionnaire
     */
    private function checkGestionnaire()
    {
        $user = Auth::user();
        if ($user && $user->role === 'tresorier') {
            abort(403, 'Action non autorisée pour le trésorier.');
        }
    }

    /**
     * Tableau de bord principal (Image 02)
     */
    public function dashboard()
    {
        // Chercher d'abord un cycle actif, sinon le dernier cycle créé (même en brouillon)
        $cycle = Cycle::where('statut', 'actif')
            ->with(['membres', 'tours.beneficiaire', 'tours.cotisations.membre', 'tours.cotisations.penalite'])
            ->latest('id')
            ->first();

        if (!$cycle) {
            $cycle = Cycle::with(['membres', 'tours.beneficiaire', 'tours.cotisations.membre', 'tours.cotisations.penalite'])
                ->latest('id')
                ->first();
        }

        $data = [
            'cycle' => null,
        ];

        if ($cycle) {
            $tourOuvert = $cycle->tours->where('statut', 'ouvert')->first();
            if (!$tourOuvert && $cycle->tours->count() > 0) {
                $tourOuvert = $cycle->tours->first();
            }

            $totalMembres = $cycle->membres->count();

            $encaisse = 0;
            $cotisationsEnRetard = 0;
            $montantRetard = 0;
            $retards = [];

            if ($tourOuvert && $tourOuvert->cotisations) {
                foreach ($tourOuvert->cotisations as $cotisation) {
                    $encaisse += $cotisation->montant_paye;
                    if ($cotisation->statut === 'en_retard' || ($cotisation->montant_paye < $cotisation->montant_attendu && !in_array($cotisation->statut, ['paye', 'payee']))) {
                        $cotisationsEnRetard++;
                        $reste = $cotisation->montant_attendu - $cotisation->montant_paye;
                        $montantRetard += $reste;
                        $membre = $cotisation->membre;
                        $penalite = $cotisation->penalite;
                        $retards[] = [
                            'membre'    => $membre ? $membre->prenom . ' ' . $membre->nom : '',
                            'telephone' => $membre ? $membre->telephone : '',
                            'reste_du'  => $reste,
                            'penalite'  => $penalite ? $penalite->montant : 0,
                        ];
                    }
                }
            }

            $totalAttendu = $tourOuvert && $tourOuvert->cotisations ? $tourOuvert->cotisations->sum('montant_attendu') : ($totalMembres * $cycle->montant_cotisation);
            $membresPayes = $tourOuvert && $tourOuvert->cotisations ? $tourOuvert->cotisations->whereIn('statut', ['paye', 'payee'])->count() : 0;
            $cagnotte = max(0, $encaisse - ($cycle->frais_gestion ?? 0));

            $data['cycle'] = [
                'id'                   => $cycle->id,
                'libelle'              => $cycle->libelle,
                'statut'               => $cycle->statut,
                'tour_actuel'          => $tourOuvert ? $tourOuvert->numero : 1,
                'total_tours'          => $cycle->tours->count() > 0 ? $cycle->tours->count() : $totalMembres,
                'date_echeance'        => $tourOuvert ? $tourOuvert->date_prevue : null,
                'encaisse'             => $encaisse,
                'total_attendu'        => $totalAttendu,
                'membres_payes'        => $membresPayes,
                'total_membres'        => $totalMembres,
                'cotisations_en_retard'=> $cotisationsEnRetard,
                'montant_retard'       => $montantRetard,
                'penalites_dues'       => count($retards),
                'montant_penalite'     => count($retards) * ($cycle->montant_penalite ?? 0),
                'cagnotte'             => $cagnotte,
                'frais_gestion'        => $cycle->frais_gestion ?? 0,
                'beneficiaire'         => $tourOuvert && $tourOuvert->beneficiaire ? [
                    'nom'  => $tourOuvert->beneficiaire->prenom . ' ' . $tourOuvert->beneficiaire->nom,
                    'rang' => $tourOuvert->numero,
                ] : null,
                'retards'              => $retards,
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
        $this->checkGestionnaire();

        $membresDisponibles = members::where('statut', 'actif')->get()->map(fn($m) => [
            'id'         => $m->id,
            'nom'        => $m->prenom . ' ' . $m->nom,
            'telephone'  => $m->telephone,
            'dans_cycle' => false,
        ]);

        return Inertia::render('Cycles/Create', [
            'membresDisponibles' => $membresDisponibles,
            'membresDuCycle'     => [],
        ]);
    }

    /**
     * Enregistrer un nouveau cycle (brouillon) avec ses membres
     */
    public function store(Request $request)
    {
        $this->checkGestionnaire();

        $data = $request->validate([
            'libelle'               => 'required|string',
            'montant_cotisation'    => 'required|numeric|min:1',
            'periodicite'           => 'required|string',
            'date_debut'            => 'required|date',
            'date_fin'              => 'nullable|date',
            'delai_tolerance_jours' => 'nullable|integer|min:0',
            'montant_penalite'      => 'nullable|numeric|min:0',
            'frais_gestion'         => 'nullable|numeric|min:0',
            'membres'               => 'nullable|array',
        ]);

        // Périodicité conforme à l'enum MySQL (mensuelle, trimestrielle, semestrielle, annuelle)
        $periodicite = strtolower(trim($data['periodicite']));
        $validPeriodicites = ['mensuelle', 'trimestrielle', 'semestrielle', 'annuelle'];
        if (!in_array($periodicite, $validPeriodicites)) {
            $periodicite = 'mensuelle';
        }

        // Calcul automatique de la date de fin si non fournie
        $membresIds = $request->input('membres', []);
        $nbMembres = max(1, count($membresIds));
        $dateDebut = Carbon::parse($data['date_debut']);

        if (empty($data['date_fin'])) {
            // Un cycle dure le nombre de tours (1 mois pour mensuelle par défaut si 1 membre ou non spécifié)
            if ($periodicite === 'mensuelle') {
                $dateFin = $dateDebut->copy()->addMonths($nbMembres);
            } elseif ($periodicite === 'trimestrielle') {
                $dateFin = $dateDebut->copy()->addMonths($nbMembres * 3);
            } elseif ($periodicite === 'semestrielle') {
                $dateFin = $dateDebut->copy()->addMonths($nbMembres * 6);
            } else {
                $dateFin = $dateDebut->copy()->addYears($nbMembres);
            }
        } else {
            $dateFin = Carbon::parse($data['date_fin']);
        }

        $userId = Auth::id() ?? 1;

        $cycle = Cycle::create([
            'libelle'               => $data['libelle'],
            'montant_cotisation'    => $data['montant_cotisation'],
            'periodicite'           => $periodicite,
            'date_debut'            => $dateDebut->toDateString(),
            'date_fin'              => $dateFin->toDateString(),
            'delai_tolerance_jours' => $data['delai_tolerance_jours'] ?? 0,
            'montant_penalite'      => $data['montant_penalite'] ?? 0,
            'frais_gestion'         => $data['frais_gestion'] ?? 0,
            'statut'                => 'brouillon',
            'createur_id'           => $userId,
        ]);

        // Enregistrement des membres sélectionnés dans la table pivot cycle_member
        if (!empty($membresIds)) {
            foreach ($membresIds as $index => $memberId) {
                CycleMembers::create([
                    'cycle_id'  => $cycle->id,
                    'member_id' => $memberId,
                    'rang'      => $index + 1,
                ]);
            }
        }

        return redirect()->route('cycles.index')->with('success', 'Cycle créé avec succès.');
    }

    /**
     * Affichage du tableau matriciel du cycle (Image 07)
     */
    public function show(Cycle $cycle)
    {
        $cycle->load([
            'membres' => fn ($q) => $q->orderBy('cycle_member.rang'),
            'tours'   => fn ($q) => $q->orderBy('numero'),
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
                    'reste_du'        => $c->montant_attendu - $c->montant_paye,
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
                'frais_gestion'      => $cycle->frais_gestion ?? 0,
            ],
            'membres'    => $membres,
            'tours'      => $tours,
            'tourOuvert' => $tourOuvert ? [
                'id'           => $tourOuvert->id,
                'numero'       => $tourOuvert->numero,
                'beneficiaire' => $tourOuvert->beneficiaire ? $tourOuvert->beneficiaire->prenom . ' ' . $tourOuvert->beneficiaire->nom : null,
            ] : null,
        ]);
    }

    /**
     * Éditer le cycle (mode brouillon)
     */
    public function edit(Cycle $cycle)
    {
        $this->checkGestionnaire();

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
     * Démarrer le cycle et générer les tours et cotisations
     */
    public function demarrer(Cycle $cycle)
    {
        $this->checkGestionnaire();

        if ($cycle->statut !== 'brouillon') {
            return redirect()->back()->with('error', 'Le cycle est déjà démarré.');
        }

        $participants = CycleMembers::where('cycle_id', $cycle->id)
            ->orderBy('rang')
            ->get();

        if ($participants->count() == 0) {
            return redirect()->back()->with('error', 'Aucun membre dans le cycle.');
        }

        $dateDebut = Carbon::parse($cycle->date_debut);

        foreach ($participants as $participant) {
            // Calcul date prévue du tour selon périodicité
            $offset = $participant->rang - 1;
            if ($cycle->periodicite === 'trimestrielle') {
                $datePrevue = $dateDebut->copy()->addMonths($offset * 3);
            } elseif ($cycle->periodicite === 'semestrielle') {
                $datePrevue = $dateDebut->copy()->addMonths($offset * 6);
            } elseif ($cycle->periodicite === 'annuelle') {
                $datePrevue = $dateDebut->copy()->addYears($offset);
            } else {
                $datePrevue = $dateDebut->copy()->addMonths($offset);
            }

            // Statut enum dans la table tours : 'en_attente', 'ouvert', 'ferme'
            $tour = Tours::create([
                'cycle_id'        => $cycle->id,
                'beneficiaire_id' => $participant->member_id,
                'numero'          => $participant->rang,
                'date_prevue'     => $datePrevue->toDateString(),
                'statut'          => $participant->rang == 1 ? 'ouvert' : 'en_attente',
            ]);

            foreach ($participants as $cotisant) {
                Cotisation::create([
                    'tour_id'         => $tour->id,
                    'membre_id'       => $cotisant->member_id,
                    'montant_attendu' => $cycle->montant_cotisation,
                    'montant_paye'    => 0,
                    'statut'          => 'en_attente',
                ]);
            }
        }

        $cycle->update(['statut' => 'actif']);

        return redirect()->route('cycles.show', $cycle->id)->with('success', 'Cycle démarré et tours générés avec succès.');
    }

    /**
     * Clôturer le tour courant et ouvrir le suivant
     */
    public function cloturerTour(Cycle $cycle, Tours $tour)
    {
        $this->checkGestionnaire();

        // 1. Fermer le tour actuel
        $tour->update(['statut' => 'ferme']);

        // 2. Ouvrir le prochain tour
        $prochainTour = Tours::where('cycle_id', $cycle->id)
            ->where('numero', $tour->numero + 1)
            ->first();

        if ($prochainTour) {
            $prochainTour->update(['statut' => 'ouvert']);
            return redirect()->back()->with('success', "Le tour {$tour->numero} a été clôturé. Le tour {$prochainTour->numero} est maintenant ouvert.");
        } else {
            // S'il n'y a plus de tour suivant, le cycle est terminé
            $cycle->update(['statut' => 'termine']);
            return redirect()->back()->with('success', "Dernier tour clôturé. Le cycle est désormais terminé.");
        }
    }

    /**
     * Clôturer le cycle
     */
    public function cloturer(Cycle $cycle)
    {
        $this->checkGestionnaire();

        $cycle->update(['statut' => 'termine']);

        return redirect()->back()->with('success', 'Cycle clôturé avec succès.');
    }

    /**
     * Supprimer un cycle
     */
    public function destroy(Cycle $cycle)
    {
        $this->checkGestionnaire();

        $cycle->delete();

        return redirect()->route('cycles.index')->with('success', 'Cycle supprimé.');
    }

    /**
     * Mettre à jour un cycle existant
     */
    public function update(Request $request, Cycle $cycle)
    {
        $this->checkGestionnaire();

        $data = $request->validate([
            'libelle'               => 'required|string',
            'montant_cotisation'    => 'required|numeric|min:1',
            'periodicite'           => 'required|string',
            'date_debut'            => 'required|date',
            'delai_tolerance_jours' => 'nullable|integer|min:0',
            'montant_penalite'      => 'nullable|numeric|min:0',
            'frais_gestion'         => 'nullable|numeric|min:0',
            'membres'               => 'nullable|array',
        ]);

        $periodicite = strtolower(trim($data['periodicite']));
        if (!in_array($periodicite, ['mensuelle', 'trimestrielle', 'semestrielle', 'annuelle'])) {
            $periodicite = 'mensuelle';
        }

        $cycle->update([
            'libelle'               => $data['libelle'],
            'montant_cotisation'    => $data['montant_cotisation'],
            'periodicite'           => $periodicite,
            'date_debut'            => $data['date_debut'],
            'delai_tolerance_jours' => $data['delai_tolerance_jours'] ?? 0,
            'montant_penalite'      => $data['montant_penalite'] ?? 0,
            'frais_gestion'         => $data['frais_gestion'] ?? 0,
        ]);

        // Mise à jour des membres du cycle si fourni
        if ($request->has('membres')) {
            CycleMembers::where('cycle_id', $cycle->id)->delete();
            foreach ($request->input('membres') as $index => $memberId) {
                CycleMembers::create([
                    'cycle_id'  => $cycle->id,
                    'member_id' => $memberId,
                    'rang'      => $index + 1,
                ]);
            }
        }

        return redirect()->route('cycles.index')->with('success', 'Cycle modifié avec succès.');
    }
}
