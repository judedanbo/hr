<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Position assignment lookups always filter on a staff member or a position
     * together with whether the assignment is still open.
     */
    public function up(): void
    {
        Schema::table('position_staff', function (Blueprint $table) {
            $table->index(['staff_id', 'end_date'], 'position_staff_staff_end_index');
            $table->index(['position_id', 'end_date'], 'position_staff_position_end_index');
        });
    }

    public function down(): void
    {
        Schema::table('position_staff', function (Blueprint $table) {
            $table->dropIndex('position_staff_staff_end_index');
            $table->dropIndex('position_staff_position_end_index');
        });
    }
};
