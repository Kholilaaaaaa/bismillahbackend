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
        Schema::create('workouts', function (Blueprint $table) {
            $table->id();
            $table->string('nama_workout');
            $table->text('deskripsi');
            $table->string('equipment');
            $table->enum('kategori', ['Without Equipment', 'With Equipment']);
            $table->integer('exercises');
            $table->enum('status', ['belum', 'sedang dilakukan', 'selesai'])->default('belum');
            $table->foreignId('jadwal_workout_id')->unique()->constrained('jadwal_workouts')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workouts');
    }
};