<?php

namespace Modules\Cycle\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Administration\Models\Notification;
use Modules\Cotisation\Models\Cotisation;
use Modules\Cotisation\Models\Penalites;
use Modules\Cycle\Models\Cycle;
use Modules\Cycle\Models\CycleMembers;
use Modules\Cycle\Models\Tours;

class TourService
{
    /**
     * Génère automatiquement les tours et cotisations pour un cycle
     */
    public function genererTours(Cycle $cycle): void
    {
        $participants = CycleMembers::where('cycle_id', $cycle->id)
            ->orderBy('rang')
            ->get();

        if ($participants->count() === 0) {
            return;
        }

        $dateDebut = Carbon::parse($cycle->date_debut);

        foreach ($participants as $index => $participant) {
            $numero = $index + 1;

            // Calcul date ouverture et échéance selon périodicité
            $dateOuverture = match ($cycle->periodicite) {
                'trimestrielle' => $dateDebut->copy()->addMonths($index * 3),
                'semestrielle'  => $dateDebut->copy()->addMonths($index * 6),
                'annuelle'      => $dateDebut->copy()->addYears($index),
                default         => $dateDebut->copy()->addMonths($index), // mensuelle
            };

            $dateEcheance = match ($cycle->periodicite) {
                'trimestrielle' => $dateOuverture->copy()->addMonths(3),
                'semestrielle'  => $dateOuverture->copy()->addMonths(6),
                'annuelle'      => $dateOuverture->copy()->addYears(1),
                default         => $dateOuverture->copy()->addMonth(), // mensuelle
            };

            // Statut initial: si date ouverture <= aujourd'hui pour le tour 1, ouvert, sinon programme / en_attente
            $today = Carbon::today();
            $statut = ($numero === 1 && $dateOuverture->lessThanOrEqualTo($today)) ? 'ouvert' : 'brouillon';

            $tour = Tours::create([
                'cycle_id'          => $cycle->id,
                'beneficiaire_id'   => $participant->member_id,
                'numero'            => $numero,
                'date_ouverture'    => $dateOuverture->toDateString(),
                'date_echeance'     => $dateEcheance->toDateString(),
                'date_prevue'       => $dateEcheance->toDateString(), // compatibilité
                'montant_distribue' => 0,
                'statut'            => $statut,
            ]);

            // Génération des cotisations attendues
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
    }

    /**
     * Traite les dates d'ouverture et d'échéance de manière automatique
     */
    public function traiterToursAutomatique(): void
    {
        $today = Carbon::today()->toDateString();

        // 1. Ouvrir les tours dont date_ouverture <= aujourd'hui et qui sont encore en brouillon/en_attente
        $toursAOuvrir = Tours::whereIn('statut', ['brouillon', 'en_attente'])
            ->whereNotNull('date_ouverture')
            ->where('date_ouverture', '<=', $today)
            ->whereHas('cycle', fn($q) => $q->where('statut', 'actif'))
            ->get();

        foreach ($toursAOuvrir as $t) {
            // Un seul tour ouvert par cycle
            $dejaOuvert = Tours::where('cycle_id', $t->cycle_id)->where('statut', 'ouvert')->exists();
            if (!$dejaOuvert) {
                $t->update(['statut' => 'ouvert']);
            }
        }

        // 2. Vérifier les échéances et appliquer le délai de tolérance
        $toursOuverts = Tours::where('statut', 'ouvert')
            ->with(['cycle', 'cotisations.membre'])
            ->get();

        foreach ($toursOuverts as $tour) {
            $cycle = $tour->cycle;
            if (!$cycle) continue;

            $dateEcheance = $tour->date_echeance ? Carbon::parse($tour->date_echeance) : ($tour->date_prevue ? Carbon::parse($tour->date_prevue) : null);
            if (!$dateEcheance) continue;

            $delaiTolerance = $cycle->delai_tolerance_jours ?? 0;
            $dateLimiteReelle = $dateEcheance->copy()->addDays($delaiTolerance);

            // Si date limite dépassée
            if (Carbon::today()->greaterThan($dateLimiteReelle)) {
                // Appliquer pénalités sur les cotisations impayées
                if ($cycle->montant_penalite > 0) {
                    foreach ($tour->cotisations as $cotisation) {
                        $reste = max(0, $cotisation->montant_attendu - $cotisation->montant_paye);
                        if ($reste > 0 && !in_array($cotisation->statut, ['paye', 'payee'])) {
                            $cotisation->update(['statut' => 'en_retard']);

                            // Créer pénalité si pas déjà créée
                            $penaliteExiste = Penalites::where('cotisation_id', $cotisation->id)->exists();
                            if (!$penaliteExiste) {
                                Penalites::create([
                                    'cotisation_id'    => $cotisation->id,
                                    'membre_id'        => $cotisation->membre_id,
                                    'cycle_id'         => $cycle->id,
                                    'tour_id'          => $tour->id,
                                    'montant'          => $cycle->montant_penalite,
                                    'statut'           => 'due',
                                    'date_application' => now()->toDateString(),
                                ]);
                            }
                        }
                    }
                }

                // Clôturer le tour automatiquement
                $this->cloturerTour($cycle, $tour, 'Délai de tolérance dépassé (clôture automatique)');
            }
        }
    }

    /**
     * Clôturer un tour et ouvrir le suivant (ou terminer le cycle)
     */
    public function cloturerTour(Cycle $cycle, Tours $tour, ?string $motifForce = null, ?int $userId = null): void
    {
        $tour->update([
            'statut'               => $motifForce ? 'cloture_forcee' : 'cloture',
            'cloture_le'           => now(),
            'motif_cloture_forcee' => $motifForce,
            'cloture_par'          => $userId,
        ]);

        // Ouvrir le tour suivant
        $prochainTour = Tours::where('cycle_id', $cycle->id)
            ->where('numero', $tour->numero + 1)
            ->first();

        if ($prochainTour) {
            $prochainTour->update(['statut' => 'ouvert']);

            Notification::create([
                'type'     => 'tour_ouvert',
                'titre'    => "Tour {$prochainTour->numero} ouvert — {$cycle->libelle}",
                'message'  => "Le tour {$tour->numero} du cycle « {$cycle->libelle} » est clôturé. Le tour {$prochainTour->numero} est désormais ouvert.",
                'cycle_id' => $cycle->id,
                'tour_id'  => $prochainTour->id,
                'lue'      => false,
            ]);
        } else {
            // Dernier tour : terminer le cycle
            $cycle->update(['statut' => 'termine']);

            Notification::create([
                'type'     => 'cycle_termine',
                'titre'    => "Cycle terminé : {$cycle->libelle}",
                'message'  => "Tous les tours du cycle « {$cycle->libelle} » ont été clôturés. Le cycle est terminé.",
                'cycle_id' => $cycle->id,
                'lue'      => false,
            ]);
        }
    }

    /**
     * Changer le bénéficiaire d'un tour (swap avec un autre tour ou affectation)
     */
    public function changerBeneficiaire(Cycle $cycle, Tours $tour, int $nouveauBeneficiaireId): void
    {
        // Vérifier si le nouveau bénéficiaire est déjà le bénéficiaire d'un autre tour dans ce cycle
        $autreTour = Tours::where('cycle_id', $cycle->id)
            ->where('beneficiaire_id', $nouveauBeneficiaireId)
            ->where('id', '!=', $tour->id)
            ->first();

        $ancienBeneficiaireId = $tour->beneficiaire_id;

        if ($autreTour) {
            // Swap entre les deux tours
            $autreTour->update(['beneficiaire_id' => $ancienBeneficiaireId]);
        }

        $tour->update(['beneficiaire_id' => $nouveauBeneficiaireId]);

        // Mettre à jour aussi le rang dans cycle_member si applicable
        if ($autreTour && $ancienBeneficiaireId) {
            CycleMembers::where('cycle_id', $cycle->id)->where('member_id', $nouveauBeneficiaireId)->update(['rang' => $tour->numero]);
            CycleMembers::where('cycle_id', $cycle->id)->where('member_id', $ancienBeneficiaireId)->update(['rang' => $autreTour->numero]);
        }
    }
}
