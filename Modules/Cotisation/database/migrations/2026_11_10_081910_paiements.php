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
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotisation_id')->constrained();
            $table->decimal('montant', 12, 2);
            $table->date('date_paiement');
            $table->string('mode');
            $table->string('reference')->nullable();
            $table->boolean('annule')->default(false);
            $table->text('motif_annulation')->nullable();
            $table->foreignId('enregistre_par')
            ->constrained('users');
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};
