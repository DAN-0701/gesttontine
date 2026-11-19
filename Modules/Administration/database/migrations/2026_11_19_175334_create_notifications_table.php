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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type');                     // 'cycle_demarre', 'tour_ouvert', 'tour_cloture', 'cycle_termine'
            $table->string('titre');
            $table->text('message');
            $table->unsignedBigInteger('cycle_id')->nullable();
            $table->unsignedBigInteger('tour_id')->nullable();
            $table->boolean('lue')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
