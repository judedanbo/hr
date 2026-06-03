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
        Schema::create('appraisal_cycles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedSmallInteger('year');
            $table->date('objective_window_start')->nullable();
            $table->date('objective_window_end')->nullable();
            $table->date('midyear_window_start')->nullable();
            $table->date('midyear_window_end')->nullable();
            $table->date('final_window_start')->nullable();
            $table->date('final_window_end')->nullable();
            $table->unsignedTinyInteger('objectives_weight')->default(70);
            $table->unsignedTinyInteger('competencies_weight')->default(30);
            $table->string('status')->default('draft');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appraisal_cycles');
    }
};
