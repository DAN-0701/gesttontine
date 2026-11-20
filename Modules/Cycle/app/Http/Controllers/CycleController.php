<?php

namespace Modules\Cycle\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Modules\Administration\Models\Notification;
use Modules\Cotisation\Models\Cotisation;
use Modules\Cycle\Models\Cycle;
use Modules\Cycle\Models\CycleMembers;
use Modules\Cycle\Models\Tours;
use Modules\Cycle\Services\TourService;
use Modules\Members\Models\members;

class CycleController extends Controller
{
    /**
     * Vérifier les permissions de l'utilisateur
     */
    private function checkPermission(string $permission)
    {
        $user = Auth::user();
        if ($user && method_exists($user, 'hasPermission')) {
            if (!$user->hasPermission($permission)) {
                abort(403, "Action non autorisée : permission '{$permission}' requise.");
            }
            return;
        }

        // Fallback rôle si hasPermission n'est pas encore chargé
        if ($user && $user->role === 'tresorier') {
            abort(403, 'Action non autorisée pour le trésorier.');
        }
    }

    private function checkGestionnaire()
    {
        $this->checkPermission('creer_cycle');
    }

    /**
     * Tableau de bord principal (Image 02)
     */
    public function dashboard(Request $request)
    {
        // Traitement automatique à la volée des cycles dont la date est atteinte
        $this->traiterDemarrageAutomatique();
        $this->traiterAvancementToursAutomatique();
        $this->traiterClotureCyclesAutomatique();

        // Récupérer tous les cycles disponibles pour le sélecteur
        $allCycles = Cycle::select('id', 'libelle', 'statut')->orderBy('id', 'desc')->get();

        // Si l'utilisateur a choisi un cycle spécifique via le paramètre GET ?cycle_id=...
        $selectedCycleId = $request->input('cycle_id');

        $cycle = null;
        if ($selectedCycleId) {
            $cycle = Cycle::with(['membres', 'tours.beneficiaire', 'tours.cotisations.membre', 'tours.cotisations.penalite'])
                ->find($selectedCycleId);
        }

        // Sinon, chercher d'abord un cycle actif, puis à défaut le dernier créé
        if (!$cycle) {
            $cycle = Cycle::where('statut', 'actif')
                ->with(['membres', 'tours.beneficiaire', 'tours.cotisations.membre', 'tours.cotisations.penalite'])
                ->latest('id')
                ->first();
        }

        if (!$cycle) {
            $cycle = Cycle::with(['membres', 'tours.beneficiaire', 'tours.cotisations.membre', 'tours.cotisations.penalite'])
                ->latest('id')
                ->first();
        }

        $data = [
            'cycle'           => null,
            'availableCycles' => $allCycles,
            'selectedCycleId' => $cycle ? $cycle->id : null,
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

            // Calcul de la date limite avec délai de tolérance (Demande 6)
            $dateToleranceLimite = null;
            $isRetardApplicable = false;

            if ($tourOuvert && $tourOuvert->date_prevue) {
                $toleranceJours = $cycle->delai_tolerance_jours ?? 0;
                $dateToleranceLimite = Carbon::parse($tourOuvert->date_prevue)->addDays($toleranceJours);
                // Si la date du jour est strictement supérieure à date_prevue + tolérance
                $isRetardApplicable = Carbon::today()->greaterThan($dateToleranceLimite);
            }

            if ($tourOuvert && $tourOuvert->cotisations) {
                foreach ($tourOuvert->cotisations as $cotisation) {
                    $encaisse += $cotisation->montant_paye;

                    $reste = max(0, $cotisation->montant_attendu - $cotisation->montant_paye);

                    // Si pas totalement payé ET que le délai de tolérance est dépassé => en retard
                    $estEnRetard = ($cotisation->statut === 'en_retard') ||
                        ($reste > 0 && !in_array($cotisation->statut, ['paye', 'payee']) && $isRetardApplicable);

                    if ($estEnRetard) {
                        $cotisationsEnRetard++;
                        $montantRetard += $reste;
                        $membre = $cotisation->membre;
                        $penalite = $cotisation->penalite;
                        $retards[] = [
                            'membre'    => $membre ? $membre->prenom . ' ' . $membre->nom : '',
                            'telephone' => $membre ? $membre->telephone : '',
                            'reste_du'  => $reste,
                            'penalite'  => $penalite ? $penalite->montant : ($cycle->montant_penalite ?? 0),
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
                'delai_tolerance'      => $cycle->delai_tolerance_jours ?? 0,
                'date_tolerance_limite'=> $dateToleranceLimite ? $dateToleranceLimite->toDateString() : null,
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
    public function index(Request $request)
    {
        // Traitement automatique à la volée des cycles dont la date est atteinte
        $this->traiterDemarrageAutomatique();
        $this->traiterAvancementToursAutomatique();
        $this->traiterClotureCyclesAutomatique();

        $query = Cycle::withCount('membres')->with('tours');

        if ($search = $request->input('search')) {
            $query->where('libelle', 'like', "%{$search}%");
        }

        if ($statut = $request->input('statut')) {
            if ($statut !== 'tous') {
                $query->where('statut', $statut);
            }
        }

        $cyclesPaginated = $query->orderBy('id', 'desc')->paginate(5)->withQueryString();

        $cyclesPaginated->through(function ($c) {
            $totalTours = $c->tours->count();
            $toursDistribues = $c->tours->whereIn('statut', ['ferme', 'distribue', 'cloture', 'cloture_forcee'])->count();
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
            'cycles' => $cyclesPaginated,
            'filters' => [
                'search' => $search,
                'statut' => $statut ?? 'tous',
            ],
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

        // Si la date de début est aujourd'hui ou antérieure, démarrer immédiatement le cycle
        $this->traiterDemarrageAutomatique();

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

        $tours = $cycle->tours->map(function ($tour) use ($cycle) {
            $toleranceJours = $cycle->delai_tolerance_jours ?? 0;
            $isRetardApplicable = false;
            if ($tour->date_prevue) {
                $limite = Carbon::parse($tour->date_prevue)->addDays($toleranceJours);
                $isRetardApplicable = Carbon::today()->greaterThan($limite);
            }

            return [
                'id'            => $tour->id,
                'numero'        => $tour->numero,
                'date_prevue'   => $tour->date_prevue,
                'statut'        => $tour->statut,
                'is_retard_applicable' => $isRetardApplicable,
                'beneficiaire'  => $tour->beneficiaire ? [
                    'id'  => $tour->beneficiaire->id,
                    'nom' => $tour->beneficiaire->prenom . ' ' . $tour->beneficiaire->nom,
                ] : null,
                'cotisations'   => $tour->cotisations->map(function ($c) use ($isRetardApplicable) {
                    $reste = max(0, $c->montant_attendu - $c->montant_paye);
                    return [
                        'id'              => $c->id,
                        'membre_id'       => $c->membre_id,
                        'montant_attendu' => $c->montant_attendu,
                        'montant_paye'    => $c->montant_paye,
                        'statut'          => $c->statut,
                        'reste_du'        => $reste,
                        'paiements'       => $c->paiements->map(fn($p) => [
                            'id'                => $p->id,
                            'montant'           => $p->montant,
                            'date_paiement'     => $p->date_paiement,
                            'mode'              => $p->mode,
                            'annule'            => (bool) $p->annule,
                            'motif_annulation'  => $p->motif_annulation,
                        ]),
                        'penalite'        => $c->penalite ? [
                            'id'                => $c->penalite->id,
                            'montant'           => $c->penalite->montant,
                            'statut'            => $c->penalite->statut,
                            'motif_annulation'  => $c->penalite->motif_annulation,
                        ] : null,
                    ];
                }),
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

        if ($cycle->statut !== 'brouillon') {
            return redirect()->route('cycles.show', $cycle->id)->with('error', 'Seul un cycle au statut brouillon peut être modifié.');
        }

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
        $this->checkPermission('demarrer_cycle');

        if ($cycle->statut !== 'brouillon') {
            return redirect()->back()->with('error', 'Le cycle est déjà démarré.');
        }

        $participants = CycleMembers::where('cycle_id', $cycle->id)
            ->orderBy('rang')
            ->get();

        if ($participants->count() == 0) {
            return redirect()->back()->with('error', 'Aucun membre dans le cycle.');
        }

        (new TourService())->genererTours($cycle);

        $cycle->update(['statut' => 'actif']);

        // Notification : cycle démarré
        Notification::create([
            'type'     => 'cycle_demarre',
            'titre'    => "Cycle démarré : {$cycle->libelle}",
            'message'  => "Le cycle « {$cycle->libelle} » vient d'être démarré. {$participants->count()} tour(s) ont été générés.",
            'cycle_id' => $cycle->id,
            'lue'      => false,
        ]);

        return redirect()->route('cycles.show', $cycle->id)->with('success', 'Cycle démarré et tours générés avec succès.');
    }

    /**
     * Changer l'ordre / bénéficiaire par les flèches monter/descendre sur les membres (Demande utilisateur)
     */
    public function swapRangMembre(Request $request, Cycle $cycle, members $membre)
    {
        $this->checkPermission('changer_beneficiaire');

        $direction = $request->input('direction'); // 'up' ou 'down'
        if (!in_array($direction, ['up', 'down'])) {
            return redirect()->back()->with('error', 'Direction invalide.');
        }

        $currentEntry = CycleMembers::where('cycle_id', $cycle->id)->where('member_id', $membre->id)->first();
        if (!$currentEntry) {
            return redirect()->back()->with('error', 'Membre non trouvé dans ce cycle.');
        }

        $currentRang = $currentEntry->rang;
        $targetRang = $direction === 'up' ? $currentRang - 1 : $currentRang + 1;

        $targetEntry = CycleMembers::where('cycle_id', $cycle->id)->where('rang', $targetRang)->first();
        if (!$targetEntry) {
            return redirect()->back()->with('error', 'Impossible de déplacer ce membre dans cette direction.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($cycle, $currentEntry, $targetEntry, $currentRang, $targetRang) {
            // Swap dans cycle_member
            $currentEntry->update(['rang' => $targetRang]);
            $targetEntry->update(['rang' => $currentRang]);

            // Swap des bénéficiaires dans la table tours si les tours existent
            $tourCurrent = Tours::where('cycle_id', $cycle->id)->where('numero', $currentRang)->first();
            $tourTarget = Tours::where('cycle_id', $cycle->id)->where('numero', $targetRang)->first();

            if ($tourCurrent && $tourTarget) {
                // Ne pas modifier les tours déjà clôturés
                if (in_array($tourCurrent->statut, ['cloture', 'cloture_forcee', 'ferme']) || in_array($tourTarget->statut, ['cloture', 'cloture_forcee', 'ferme'])) {
                    throw new \Exception('Impossible de modifier le bénéficiaire d\'un tour déjà clôturé.');
                }

                $memberIdCurrent = $currentEntry->member_id;
                $memberIdTarget = $targetEntry->member_id;

                $tourCurrent->update(['beneficiaire_id' => $memberIdTarget]);
                $tourTarget->update(['beneficiaire_id' => $memberIdCurrent]);
            }
        });

        return redirect()->back()->with('success', 'Ordre des bénéficiaires mis à jour avec succès.');
    }

    /**
     * Clôture forcée d'un tour avec motif obligatoire
     */
    public function clotureForceeTour(Request $request, Cycle $cycle, Tours $tour)
    {
        $this->checkPermission('cloturer_tour');

        $request->validate([
            'motif' => 'required|string|min:3',
        ]);

        (new TourService())->cloturerTour($cycle, $tour, $request->input('motif'), Auth::id());

        return redirect()->back()->with('success', "Le tour {$tour->numero} a fait l'objet d'une clôture forcée avec succès.");
    }

    /**
     * Clôturer le tour courant et ouvrir le suivant
     */
    public function cloturerTour(Cycle $cycle, Tours $tour)
    {
        $this->checkPermission('cloturer_tour');

        (new TourService())->cloturerTour($cycle, $tour, null, Auth::id());

        return redirect()->back()->with('success', "Le tour {$tour->numero} a été clôturé.");
    }

    /**
     * Clôturer le cycle
     */
    public function cloturer(Cycle $cycle)
    {
        $this->checkPermission('cloturer_cycle');

        $cycle->update(['statut' => 'termine']);

        return redirect()->back()->with('success', 'Cycle clôturé avec succès.');
    }

    /**
     * Supprimer un cycle
     */
    public function destroy(Cycle $cycle)
    {
        $this->checkGestionnaire();

        if ($cycle->statut !== 'brouillon') {
            return redirect()->back()->with('error', 'Seul un cycle au statut brouillon peut être supprimé.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($cycle) {
            // Supprimer les cotisations et pénalités associées aux tours du cycle
            $tourIds = $cycle->tours()->pluck('id')->toArray();
            if (!empty($tourIds)) {
                $cotisationIds = \Modules\Cotisation\Models\Cotisation::whereIn('tour_id', $tourIds)->pluck('id')->toArray();
                if (!empty($cotisationIds)) {
                    \Modules\Cotisation\Models\Penalites::whereIn('cotisation_id', $cotisationIds)->delete();
                    \Modules\Cotisation\Models\Paiements::whereIn('cotisation_id', $cotisationIds)->delete();
                    \Modules\Cotisation\Models\Cotisation::whereIn('id', $cotisationIds)->delete();
                }
                \Modules\Cycle\Models\Tours::whereIn('id', $tourIds)->delete();
            }

            // Supprimer les liaisons cycle_member
            CycleMembers::where('cycle_id', $cycle->id)->delete();

            // Supprimer les notifications liées
            \Modules\Administration\Models\Notification::where('cycle_id', $cycle->id)->delete();

            // Supprimer le cycle
            $cycle->delete();
        });

        return redirect()->route('cycles.index')->with('success', 'Cycle brouillon supprimé avec succès.');
    }

    /**
     * Mettre à jour un cycle existant
     */
    public function update(Request $request, Cycle $cycle)
    {
        $this->checkGestionnaire();

        if ($cycle->statut !== 'brouillon') {
            return redirect()->route('cycles.show', $cycle->id)->with('error', 'Seul un cycle au statut brouillon peut être modifié.');
        }

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

        $periodicite = strtolower(trim($data['periodicite']));
        $validPeriodicites = ['mensuelle', 'trimestrielle', 'semestrielle', 'annuelle'];
        if (!in_array($periodicite, $validPeriodicites)) {
            $periodicite = 'mensuelle';
        }

        $membresIds = $request->input('membres', []);
        $nbMembres = max(1, count($membresIds));
        $dateDebut = Carbon::parse($data['date_debut']);

        if (empty($data['date_fin'])) {
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

        $cycle->update([
            'libelle'               => $data['libelle'],
            'montant_cotisation'    => $data['montant_cotisation'],
            'periodicite'           => $periodicite,
            'date_debut'            => $dateDebut->toDateString(),
            'date_fin'              => $dateFin->toDateString(),
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

        // Si la date de début est aujourd'hui ou passée, démarrer immédiatement le cycle
        $this->traiterDemarrageAutomatique();

        return redirect()->route('cycles.index')->with('success', 'Cycle modifié avec succès.');
    }

    /**
     * Ajouter des membres à un cycle
     */
    public function ajoutermembres(Request $request, Cycle $cycle)
    {
        $this->checkPermission('creer_cycle');

        $data = $request->validate([
            'member_id' => 'nullable|exists:members,id',
            'membres'   => 'nullable|array',
        ]);

        $memberIds = $request->input('membres', []);
        if ($request->filled('member_id')) {
            $memberIds[] = $request->input('member_id');
        }

        $currentMaxRang = CycleMembers::where('cycle_id', $cycle->id)->max('rang') ?? 0;

        foreach (array_unique($memberIds) as $mid) {
            $exists = CycleMembers::where('cycle_id', $cycle->id)->where('member_id', $mid)->exists();
            if (!$exists) {
                $currentMaxRang++;
                CycleMembers::create([
                    'cycle_id'  => $cycle->id,
                    'member_id' => $mid,
                    'rang'      => $currentMaxRang,
                ]);
            }
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Membres ajoutés avec succès']);
        }

        return redirect()->back()->with('success', 'Membres ajoutés au cycle avec succès.');
    }

    /**
     * Modifier l'ordre des rangs des membres du cycle
     */
    public function modifierrangs(Request $request, Cycle $cycle)
    {
        $this->checkPermission('creer_cycle');

        $data = $request->validate([
            'rangs' => 'required|array',
        ]);

        foreach ($data['rangs'] as $item) {
            if (isset($item['member_id']) && isset($item['rang'])) {
                CycleMembers::where('cycle_id', $cycle->id)
                    ->where('member_id', $item['member_id'])
                    ->update(['rang' => $item['rang']]);
            }
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Rangs mis à jour avec succès']);
        }

        return redirect()->back()->with('success', 'Rangs mis à jour avec succès.');
    }

    /*
    |--------------------------------------------------------------------------
    | Traitements automatiques pour la commande cycles:update (Demande 13)
    |--------------------------------------------------------------------------
    | Aucune couche Service/Repository : toute la logique réside dans le contrôleur.
    */

    /**
     * 1. Démarrage automatique des cycles dont la date_debut est atteinte ou dépassée
     */
    public function traiterDemarrageAutomatique(): int
    {
        $today = Carbon::today()->toDateString();

        $cyclesADemarrer = Cycle::whereIn('statut', ['en_attente', 'brouillon'])
            ->where('date_debut', '<=', $today)
            ->get();

        $count = 0;
        $tourService = new TourService();

        foreach ($cyclesADemarrer as $cycle) {
            $participants = CycleMembers::where('cycle_id', $cycle->id)->orderBy('rang')->get();
            if ($participants->count() == 0) {
                continue;
            }

            if ($cycle->tours()->count() == 0) {
                $tourService->genererTours($cycle);
            } else {
                $premierTour = $cycle->tours()->where('numero', 1)->first();
                if ($premierTour && in_array($premierTour->statut, ['en_attente', 'brouillon'])) {
                    $premierTour->update(['statut' => 'ouvert']);
                }
            }

            $cycle->update(['statut' => 'actif']);
            Notification::create([
                'type'     => 'cycle_demarre',
                'titre'    => "Cycle démarré automatiquement : {$cycle->libelle}",
                'message'  => "Le cycle « {$cycle->libelle} » a été démarré automatiquement car sa date de début ({$cycle->date_debut}) est atteinte.",
                'cycle_id' => $cycle->id,
                'lue'      => false,
            ]);
            $count++;
        }

        // Exécuter également la vérification des échéances et tolérance
        $tourService->traiterToursAutomatique();

        return $count;
    }

    /**
     * 2. Gestion automatique des tours
     * Un seul tour ouvert à la fois. Lorsque la date_prevue (date de fin du tour) est atteinte,
     * le tour passe à 'ferme' et le tour suivant est automatiquement ouvert.
     */
    public function traiterAvancementToursAutomatique(): int
    {
        $today = Carbon::today()->toDateString();

        // Récupérer les tours 'ouvert' dont la date_prevue est atteinte ou dépassée
        $toursEchus = Tours::where('statut', 'ouvert')
            ->whereNotNull('date_prevue')
            ->where('date_prevue', '<=', $today)
            ->with('cycle')
            ->get();

        $count = 0;

        foreach ($toursEchus as $tour) {
            // Clôturer le tour courant
            $tour->update(['statut' => 'ferme']);

            // Vérifier s'il existe un tour suivant
            $prochainTour = Tours::where('cycle_id', $tour->cycle_id)
                ->where('numero', $tour->numero + 1)
                ->first();

            if ($prochainTour) {
                // S'assurer qu'aucun autre tour n'est ouvert et ouvrir le suivant
                $prochainTour->update(['statut' => 'ouvert']);
                $cycleNom = $tour->cycle ? $tour->cycle->libelle : 'inconnu';
                Notification::create([
                    'type'     => 'tour_ouvert',
                    'titre'    => "Tour {$prochainTour->numero} ouvert automatiquement",
                    'message'  => "Le tour {$tour->numero} du cycle « {$cycleNom} » a été clôturé automatiquement. Le tour {$prochainTour->numero} est maintenant ouvert.",
                    'cycle_id' => $tour->cycle_id,
                    'tour_id'  => $prochainTour->id,
                    'lue'      => false,
                ]);
            } else {
                // Aucun tour suivant, clôture du cycle
                if ($tour->cycle) {
                    $tour->cycle->update(['statut' => 'termine']);
                    Notification::create([
                        'type'     => 'cycle_termine',
                        'titre'    => "Cycle terminé automatiquement : {$tour->cycle->libelle}",
                        'message'  => "Tous les tours du cycle « {$tour->cycle->libelle} » ont été complétés. Le cycle est maintenant terminé.",
                        'cycle_id' => $tour->cycle_id,
                        'lue'      => false,
                    ]);
                }
            }

            $count++;
        }

        return $count;
    }

    /**
     * 3. Clôture automatique du cycle
     * Si tous les tours sont clôturés ou si la date_fin du cycle est atteinte.
     */
    public function traiterClotureCyclesAutomatique(): int
    {
        $today = Carbon::today()->toDateString();
        $cyclesActifs = Cycle::where('statut', 'actif')->with('tours')->get();
        $count = 0;

        foreach ($cyclesActifs as $cycle) {
            $tousToursClotures = ($cycle->tours->count() > 0) &&
                $cycle->tours->every(fn($t) => in_array($t->statut, ['ferme', 'cloture']));

            $dateFinAtteinte = $cycle->date_fin && ($cycle->date_fin <= $today);

            if ($tousToursClotures || $dateFinAtteinte) {
                $cycle->update(['statut' => 'termine']);
                $count++;
            }
        }

        return $count;
    }
}
