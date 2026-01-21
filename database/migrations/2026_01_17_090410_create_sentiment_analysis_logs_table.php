<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sentiment_analysis_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('total_feedback');
            $table->integer('positive_count')->default(0);
            $table->integer('negative_count')->default(0);
            $table->integer('neutral_count')->default(0);
            $table->decimal('mrr_score', 5, 4)->default(0);
            $table->datetime('analysis_date');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sentiment_analysis_logs');
    }
};