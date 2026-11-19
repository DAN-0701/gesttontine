<?php

namespace Modules\Administration\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Administration\Models\Permission;

class AdministrationDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            [
                'nom' => 'creer_membre',
                'description' => 'Créer de nouveaux membres dans la tontine',
            ],
            [
                'nom' => 'modifier_membre',
                'description' => 'Modifier les informations d\'un membre existant',
            ],
            [
                'nom' => 'supprimer_membre',
                'description' => 'Supprimer un membre de la tontine',
            ],
            [
                'nom' => 'creer_cycle',
                'description' => 'Créer et configurer un nouveau cycle de tontine',
            ],
            [
                'nom' => 'demarrer_cycle',
                'description' => 'Démarrer un cycle en brouillon et générer ses tours',
            ],
            [
                'nom' => 'cloturer_cycle',
                'description' => 'Clôturer définitivement un cycle actif',
            ],
            [
                'nom' => 'cloturer_tour',
                'description' => 'Clôturer le tour en cours et ouvrir le tour suivant',
            ],
            [
                'nom' => 'enregistrer_paiement',
                'description' => 'Enregistrer un versement de cotisation pour un membre',
            ],
            [
                'nom' => 'corriger_paiement',
                'description' => 'Modifier ou corriger un paiement déjà saisi',
            ],
            [
                'nom' => 'annuler_paiement',
                'description' => 'Annuler un paiement enregistré avec motif justificatif',
            ],
            [
                'nom' => 'payer_penalite',
                'description' => 'Enregistrer le règlement d\'une pénalité de retard',
            ],
            [
                'nom' => 'annuler_penalite',
                'description' => 'Annuler une pénalité avec motif justificatif',
            ],
            [
                'nom' => 'consulter',
                'description' => 'Consulter les tableaux de bord, cycles et membres',
            ],
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['nom' => $p['nom']], $p);
        }
    }
}
