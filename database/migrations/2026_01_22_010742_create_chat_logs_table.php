<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chat_logs', function (Blueprint $table) {
            $table->id();
            $table->text('question');
            $table->longText('answer');
            $table->string('model_type')->default('llm'); // llm, h5, database, hybrid
            $table->decimal('confidence', 3, 2)->nullable(); // 0.00 - 1.00
            $table->timestamps();
        });
        
        // Tambah kolom di chatbot_knowledge jika belum ada
        if (Schema::hasTable('chatbot_knowledge')) {
            if (!Schema::hasColumn('chatbot_knowledge', 'model_type')) {
                Schema::table('chatbot_knowledge', function (Blueprint $table) {
                    $table->string('model_type')->default('csv')->after('source');
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_logs');
        
        // Hapus kolom model_type dari chatbot_knowledge jika ada
        if (Schema::hasTable('chatbot_knowledge') && Schema::hasColumn('chatbot_knowledge', 'model_type')) {
            Schema::table('chatbot_knowledge', function (Blueprint $table) {
                $table->dropColumn('model_type');
            });
        }
    }
};