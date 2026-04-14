<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds remaining performance indexes for name searches and staff lookups.
     */
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->index('first_name', 'idx_people_first_name');
            $table->index('surname', 'idx_people_surname');
        });

        Schema::table('institution_person', function (Blueprint $table) {
            $table->index('staff_number', 'idx_institution_person_staff_number');
            $table->index('file_number', 'idx_institution_person_file_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropIndex('idx_people_first_name');
            $table->dropIndex('idx_people_surname');
        });

        Schema::table('institution_person', function (Blueprint $table) {
            $table->dropIndex('idx_institution_person_staff_number');
            $table->dropIndex('idx_institution_person_file_number');
        });
    }
};
