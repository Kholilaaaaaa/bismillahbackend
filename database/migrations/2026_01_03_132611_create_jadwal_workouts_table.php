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
        Schema::create('jadwal_workouts', function (Blueprint $table) {
            $table->id();
            $table->string('nama_jadwal');
            $table->string('kategori_jadwal');
            $table->date('tanggal');
            $table->time('jam');
            $table->string('durasi_workout');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal_workouts');
    }
};