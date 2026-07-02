<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Guarded with hasColumn so it is safe regardless of merge order with any
     * other branch that also introduces units.head_staff_id (e.g. Leave PR #44).
     */
    public function up(): void
    {
        if (! Schema::hasColumn('units', 'head_staff_id')) {
            Schema::table('units', function (Blueprint $table) {
                $table->foreignId('head_staff_id')
                    ->nullable()
                    ->after('institution_id')
                    ->constrained('institution_person')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('units', 'head_staff_id')) {
            Schema::table('units', function (Blueprint $table) {
                $table->dropConstrainedForeignId('head_staff_id');
            });
        }
    }
};
