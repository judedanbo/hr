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
        Schema::create('leave_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_category_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('days_allowed');
            $table->unsignedSmallInteger('min_service_months')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['leave_year_id', 'leave_type_id', 'job_category_id'],
                'leave_entitlements_year_type_category_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_entitlements');
    }
};
