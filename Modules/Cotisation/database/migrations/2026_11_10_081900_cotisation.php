<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cotisations', function (Blueprint $table) {
        $table->id();
        $table->foreignId('tour_id')->constrained('tours');
        $table->foreignId('membre_id')->constrained('members');
        $table->decimal(
                'montant_attendu',
                12,
                2
            );
        $table->decimal(
            'montant_paye',
            12,
            2
        )->default(0);
        $table->enum(
            'statut',
            [
                'en_attente',
                'partielle',
                'payee',
                'en_retard'
            ]
        )->default('en_attente');

});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cotisations');
    }
};
