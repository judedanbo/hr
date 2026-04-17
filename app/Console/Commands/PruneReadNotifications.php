<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;

class PruneReadNotifications extends Command
{
    protected $signature = 'notifications:prune {--days=90 : Read notifications older than this many days will be deleted}';

    protected $description = 'Delete read notifications older than the configured retention window.';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $deleted = DatabaseNotification::query()
            ->whereNotNull('read_at')
            ->where('read_at', '<', $cutoff)
            ->delete();

        $this->info("Pruned {$deleted} read notification(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
