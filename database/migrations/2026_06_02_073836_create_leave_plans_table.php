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
        Schema::create('leave_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('institution_person')->cascadeOnDelete();
            $table->foreignId('leave_year_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('Draft');
            $table->dateTime('submitted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['staff_id', 'leave_year_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_plans');
    }
};
