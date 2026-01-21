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
        Schema::create('detail_workouts', function (Blueprint $table) {
            $table->id('id_detail_workout');

            $table->foreignId('id_workout')
                  ->constrained('workouts')
                  ->onDelete('cascade');

            // 🔒 LABEL GERAKAN DIKUNCI
            $table->enum('label_ml', [
                'pushup',
                'shoulder_press',
                't_bar_row'
            ]);

            $table->integer('repetisi')->nullable();
            $table->integer('set')->nullable();
            $table->integer('durasi_detik')->nullable();
            $table->text('catatan')->nullable();

            // 🔢 ORDER GERAKAN (1,2,3)
            $table->integer('urutan')->default(1);

            $table->timestamps();

            $table->index('id_workout');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_workouts');
    }
};