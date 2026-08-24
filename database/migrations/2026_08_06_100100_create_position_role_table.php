<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which application roles a position confers on whoever holds it.
     *
     * This is configuration, so it is not soft-deleted; the record of who
     * actually received a role lives in position_role_grants.
     */
    public function up(): void
    {
        Schema::create('position_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_id')->constrained('positions')->cascadeOnDelete();
            $table->foreignId('role_id')
                ->constrained(config('permission.table_names.roles'))
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['position_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('position_role');
    }
};
