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
        Schema::create('appraisal_competency_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appraisal_id')->constrained('appraisals')->cascadeOnDelete();
            $table->foreignId('appraisal_competency_id')->nullable()->constrained('appraisal_competencies')->nullOnDelete();
            $table->unsignedTinyInteger('weight')->default(0);
            $table->decimal('self_score', 6, 2)->nullable();
            $table->decimal('supervisor_score', 6, 2)->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appraisal_competency_ratings');
    }
};
