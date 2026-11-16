<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('cycles', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->decimal('montant_cotisation', 15, 2);
            $table->enum('periodicite', ['mensuelle', 'trimestrielle', 'semestrielle', 'annuelle']);
            $table->date('date_debut');
            $table->date('date_fin');
            $table->integer('delai_tolerance_jours')->default(0);
            $table->decimal('montant_penalite',10,2)->default(0);
            $table->decimal('frais_gestion',10,2)->default(0);
            $table->enum('statut',
                [
                    'brouillon',
                    'actif',
                    'termine'
                ]
            );
            $table->foreignId('createur_id')->constrained('users');
        });
        
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('cycles');
        
        
    }
};
