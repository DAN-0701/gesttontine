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
        Schema::create('tours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained();
            $table->integer('numero');
            $table->date('date_prevue')->nullable();
            $table->enum('statut',[
                'en_attente',
                'ouvert',
                'ferme'
            ]);
            $table->foreignId('beneficiaire_id')
            ->nullable()
            ->constrained('members');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('tours');
    }
};
