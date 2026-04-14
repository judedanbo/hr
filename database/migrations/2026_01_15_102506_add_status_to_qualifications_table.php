<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('qualifications', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('year');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('status');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });

        // Set existing qualifications to 'approved' so they remain visible
        DB::table('qualifications')->update(['status' => 'approved']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qualifications', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['status', 'approved_by', 'approved_at']);
        });
    }
};
