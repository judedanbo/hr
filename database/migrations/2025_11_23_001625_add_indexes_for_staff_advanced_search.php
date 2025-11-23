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
        // Add indexes to job_staff table for rank filtering
        Schema::table('job_staff', function (Blueprint $table) {
            $table->index('job_id', 'idx_job_staff_job_id');
            $table->index('end_date', 'idx_job_staff_end_date');
            $table->index(['staff_id', 'end_date'], 'idx_job_staff_staff_end');
        });

        // Add indexes to staff_unit table for unit/department filtering
        Schema::table('staff_unit', function (Blueprint $table) {
            $table->index('unit_id', 'idx_staff_unit_unit_id');
            $table->index('end_date', 'idx_staff_unit_end_date');
            $table->index(['staff_id', 'end_date'], 'idx_staff_unit_staff_end');
        });

        // Add indexes to jobs table for category filtering
        Schema::table('jobs', function (Blueprint $table) {
            $table->index('job_category_id', 'idx_jobs_category_id');
        });

        // Add index to people table for gender filtering
        Schema::table('people', function (Blueprint $table) {
            $table->index('gender', 'idx_people_gender');
            $table->index('date_of_birth', 'idx_people_dob');
        });

        // Add index to institution_person table for hire date filtering
        Schema::table('institution_person', function (Blueprint $table) {
            $table->index('hire_date', 'idx_institution_person_hire_date');
        });

        // Add indexes to statuses table for status filtering
        Schema::table('statuses', function (Blueprint $table) {
            $table->index(['staff_id', 'status', 'end_date'], 'idx_statuses_staff_status_end');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes from job_staff table
        Schema::table('job_staff', function (Blueprint $table) {
            $table->dropIndex('idx_job_staff_job_id');
            $table->dropIndex('idx_job_staff_end_date');
            $table->dropIndex('idx_job_staff_staff_end');
        });

        // Drop indexes from staff_unit table
        Schema::table('staff_unit', function (Blueprint $table) {
            $table->dropIndex('idx_staff_unit_unit_id');
            $table->dropIndex('idx_staff_unit_end_date');
            $table->dropIndex('idx_staff_unit_staff_end');
        });

        // Drop indexes from jobs table
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex('idx_jobs_category_id');
        });

        // Drop indexes from people table
        Schema::table('people', function (Blueprint $table) {
            $table->dropIndex('idx_people_gender');
            $table->dropIndex('idx_people_dob');
        });

        // Drop index from institution_person table
        Schema::table('institution_person', function (Blueprint $table) {
            $table->dropIndex('idx_institution_person_hire_date');
        });

        // Drop indexes from statuses table
        Schema::table('statuses', function (Blueprint $table) {
            $table->dropIndex('idx_statuses_staff_status_end');
        });
    }
};
