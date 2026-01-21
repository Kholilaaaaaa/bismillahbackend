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
        Schema::create('foods', function (Blueprint $table) {
            $table->id();
            $table->string('nama_makanan');
            $table->text('deskripsi')->nullable();
            $table->string('kategori_makanan');
            $table->integer('kalori');
            $table->decimal('protein', 8, 2);
            $table->decimal('karbohidrat', 8, 2);
            $table->decimal('lemak', 8, 2);
            $table->timestamps();
            
            $table->index('kategori_makanan');
            $table->index('nama_makanan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('foods');
    }
};