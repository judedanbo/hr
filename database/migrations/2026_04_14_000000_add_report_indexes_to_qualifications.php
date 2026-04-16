<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
            foreach ([
                'qualifications_level_status_index',
                'qualifications_status_approved_at_index',
                'qualifications_year_index',
                'qualifications_institution_index',
            ] as $index) {
                if ($this->hasIndex('qualifications', $index)) {
                    $table->dropIndex($index);
                }
            }
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn (array $existing) => $existing['name'] === $index);
    }
};
