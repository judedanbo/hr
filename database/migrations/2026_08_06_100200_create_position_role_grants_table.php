<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Provenance for roles handed out by a position assignment.
     *
     * Revocation must never strip a role an administrator assigned by hand,
     * and a user can hold the same role from more than one position, so each
     * grant is recorded against the assignment that produced it.
     * `was_preexisting` marks a role the user already held for reasons of its
     * own, which revocation leaves alone.
     */
    public function up(): void
    {
        Schema::create('position_role_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')
                ->constrained(config('permission.table_names.roles'))
                ->cascadeOnDelete();
            $table->foreignId('position_staff_id')->constrained('position_staff')->cascadeOnDelete();
            $table->foreignId('position_id')->constrained('positions')->cascadeOnDelete();
            $table->boolean('was_preexisting')->default(false);
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['position_staff_id', 'role_id']);
            $table->index(['user_id', 'role_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('position_role_grants');
    }
};
