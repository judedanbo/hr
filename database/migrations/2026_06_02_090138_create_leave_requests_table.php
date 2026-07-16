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
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('institution_person')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_plan_item_id')->nullable()->constrained()->nullOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('requested_days');
            $table->text('reason')->nullable();
            $table->string('address_during_leave');
            $table->string('contact_during_leave');
            $table->foreignId('relieving_officer_id')->nullable()->constrained('institution_person')->nullOnDelete();
            $table->string('status')->default('Pending');

            // Reserved for Phase 4 (approvals) and later
            $table->unsignedSmallInteger('approved_days')->nullable();
            $table->foreignId('approver_id')->nullable()->constrained('institution_person')->nullOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('decided_at')->nullable();
            $table->text('decline_reason')->nullable();
            $table->boolean('is_backdated')->default(false);
            $table->date('actual_return_date')->nullable();
            $table->unsignedSmallInteger('actual_days')->nullable();
            $table->foreignId('amended_from_id')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
