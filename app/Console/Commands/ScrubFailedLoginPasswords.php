<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class ScrubFailedLoginPasswords extends Command
{
    /**
     * How many entries one UPDATE changes, so no statement holds many rows of
     * a table the application writes to on almost every request.
     */
    public const CHUNK = 500;

    protected $signature = 'app:scrub-failed-login-passwords
        {--dry-run : Report how many entries hold a password without changing them}';

    protected $description = 'Remove the typed password that failed sign-ins used to store in the activity log.';

    public function handle(): int
    {
        if ($this->option('dry-run')) {
            $this->info('Would scrub: ' . $this->entriesHoldingAPassword()->count());
            $this->comment('Dry run: nothing was changed.');

            return self::SUCCESS;
        }

        $scrubbed = 0;

        $this->entriesHoldingAPassword()
            ->select('id')
            ->chunkById(self::CHUNK, function (Collection $entries) use (&$scrubbed): void {
                $scrubbed += $this->removePasswordFrom($entries);
            });

        $this->info('Scrubbed: ' . $scrubbed);

        return self::SUCCESS;
    }

    /**
     * Failed sign-ins recorded while the typed password was stored beside the
     * email.
     *
     * Only the login event is matched. Entries about changes to a user can
     * hold a password key as well, but that is a hash written by a different
     * part of the application, and it is not what this command is for.
     */
    private function entriesHoldingAPassword(): Builder
    {
        return Activity::query()
            ->where('event', 'login')
            ->whereJsonContainsKey('properties->attributes->password');
    }

    /**
     * Remove the password key and nothing else.
     *
     * The key is removed inside the database, so the passwords are never read
     * into the application, and the update goes round the model so that
     * updated_at keeps the time the entry was recorded.
     *
     * @param  Collection<int, Activity>  $entries
     * @return int the number of entries changed
     */
    private function removePasswordFrom(Collection $entries): int
    {
        return Activity::query()
            ->whereKey($entries->modelKeys())
            ->toBase()
            ->update(['properties' => DB::raw("JSON_REMOVE(properties, '$.attributes.password')")]);
    }
}
