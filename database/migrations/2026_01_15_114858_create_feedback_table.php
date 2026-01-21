<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pengguna')
                ->constrained('penggunas', 'id')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->integer('rating'); // Nilai rating 1-5
            $table->text('review')->nullable(); // Ulasan teks
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};