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
        Schema::create('penalites', function ( Blueprint $table) {
        $table->id();
        $table->foreignId('cotisation_id')->constrained();
        $table->decimal('montant',12,2);
        $table->enum('statut',
        [
            'due',
            'payee',
            'annulee'
        ]
    )->default('due');

    $table->date('date_application');
    $table->date('date_paiement')->nullable();
    $table->text('motif_annulation')->nullable();
    $table->foreignId('traite_par')
            ->nullable()
            ->constrained('users');
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penalites');
    }
};
