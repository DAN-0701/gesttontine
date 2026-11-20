<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Mettre à jour la table tours
        Schema::table('tours', function (Blueprint $table) {
            if (!Schema::hasColumn('tours', 'date_ouverture')) {
                $table->date('date_ouverture')->nullable()->after('numero');
            }
            if (!Schema::hasColumn('tours', 'date_echeance')) {
                $table->date('date_echeance')->nullable()->after('date_ouverture');
            }
            if (!Schema::hasColumn('tours', 'montant_distribue')) {
                $table->decimal('montant_distribue', 15, 2)->default(0)->after('beneficiaire_id');
            }
            if (!Schema::hasColumn('tours', 'distribue_le')) {
                $table->timestamp('distribue_le')->nullable()->after('montant_distribue');
            }
            if (!Schema::hasColumn('tours', 'cloture_le')) {
                $table->timestamp('cloture_le')->nullable()->after('distribue_le');
            }
            if (!Schema::hasColumn('tours', 'motif_cloture_forcee')) {
                $table->string('motif_cloture_forcee')->nullable()->after('cloture_le');
            }
            if (!Schema::hasColumn('tours', 'cloture_par')) {
                $table->foreignId('cloture_par')->nullable()->after('motif_cloture_forcee')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('tours', 'created_at')) {
                $table->timestamps();
            }
        });

        // Modifier la colonne statut pour accepter les nouveaux statuts (VARCHAR 50)
        DB::statement("ALTER TABLE tours MODIFY COLUMN statut VARCHAR(50) NOT NULL DEFAULT 'brouillon'");

        // Synchroniser date_echeance et date_ouverture depuis date_prevue pour les tours déjà en base
        DB::statement("UPDATE tours SET date_echeance = date_prevue WHERE date_echeance IS NULL AND date_prevue IS NOT NULL");
        DB::statement("UPDATE tours SET date_ouverture = date_prevue WHERE date_ouverture IS NULL AND date_prevue IS NOT NULL");

        // 2. Mettre à jour la table penalites
        Schema::table('penalites', function (Blueprint $table) {
            if (!Schema::hasColumn('penalites', 'membre_id')) {
                $table->foreignId('membre_id')->nullable()->after('id')->constrained('members')->cascadeOnDelete();
            }
            if (!Schema::hasColumn('penalites', 'cycle_id')) {
                $table->foreignId('cycle_id')->nullable()->after('membre_id')->constrained('cycles')->cascadeOnDelete();
            }
            if (!Schema::hasColumn('penalites', 'tour_id')) {
                $table->foreignId('tour_id')->nullable()->after('cycle_id')->constrained('tours')->cascadeOnDelete();
            }
            if (!Schema::hasColumn('penalites', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
