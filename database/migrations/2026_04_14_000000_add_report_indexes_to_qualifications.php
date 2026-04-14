<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qualifications', function (Blueprint $table) {
            if (! $this->hasIndex('qualifications', 'qualifications_level_status_index')) {
                $table->index(['level', 'status'], 'qualifications_level_status_index');
            }
            if (! $this->hasIndex('qualifications', 'qualifications_status_approved_at_index')) {
                $table->index(['status', 'approved_at'], 'qualifications_status_approved_at_index');
            }
            if (! $this->hasIndex('qualifications', 'qualifications_year_index')) {
                $table->index('year', 'qualifications_year_index');
            }
            if (! $this->hasIndex('qualifications', 'qualifications_institution_index')) {
                $table->index('institution', 'qualifications_institution_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('qualifications', function (Blueprint $table) {
            $table->dropIndex('qualifications_level_status_index');
            $table->dropIndex('qualifications_status_approved_at_index');
            $table->dropIndex('qualifications_year_index');
            $table->dropIndex('qualifications_institution_index');
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        $db = DB::getDatabaseName();

        return DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$db, $table, $index]
        )->c > 0;
    }
};
