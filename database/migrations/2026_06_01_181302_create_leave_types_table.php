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
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('requires_evidence')->default(false);
            $table->string('gender_restriction')->nullable();
            $table->boolean('counts_weekends')->default(false);
            $table->boolean('counts_holidays')->default(false);
            $table->unsignedSmallInteger('min_notice_days')->default(0);
            $table->unsignedSmallInteger('max_consecutive_days')->nullable();
            $table->unsignedSmallInteger('max_concurrent_per_unit')->nullable();
            $table->string('color')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
