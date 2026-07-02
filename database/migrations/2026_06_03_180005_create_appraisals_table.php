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
        Schema::create('appraisals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appraisal_cycle_id')->constrained('appraisal_cycles')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('institution_person')->cascadeOnDelete();
            $table->foreignId('appraiser_id')->nullable()->constrained('institution_person')->nullOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('institution_person')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('status')->default('draft_objectives');
            $table->timestamp('self_submitted_at')->nullable();
            $table->decimal('objectives_score', 6, 2)->nullable();
            $table->decimal('competencies_score', 6, 2)->nullable();
            $table->decimal('supervisor_score', 6, 2)->nullable();
            $table->decimal('overall_score', 6, 2)->nullable();
            $table->string('overall_band')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['appraisal_cycle_id', 'staff_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appraisals');
    }
};
