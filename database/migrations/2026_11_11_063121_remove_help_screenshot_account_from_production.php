<?php

use App\Enums\ContactTypeEnum;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const ACCOUNT = 'screenshots@help.test';

    private const SAMPLE_OFFICIAL_EMAIL = 'staff.member@audit.gov.gh';

    /**
     * Remove the help-screenshot account where APP_ENV is production.
     *
     * HelpScreenshotSeeder makes that account a super-administrator for local
     * screenshot runs, and its password used to be written in the repository.
     * The seeder now refuses to run in production; this removes an account an
     * earlier run may have left behind, with its roles, tokens and
     * notifications.
     *
     * The seeder also put a sample official email on the record the account
     * was attached to. With the account gone that address could be used to
     * register as that person, so it is removed from that one record.
     *
     * Elsewhere the account is left alone: the screenshot run needs it.
     */
    public function up(): void
    {
        if (! app()->environment('production')) {
            return;
        }

        User::query()->where('email', self::ACCOUNT)->get()->each(function (User $account): void {
            if ($account->person_id !== null) {
                Contact::query()
                    ->where('person_id', $account->person_id)
                    ->where('contact_type', ContactTypeEnum::EMAIL)
                    ->where('contact', self::SAMPLE_OFFICIAL_EMAIL)
                    ->get()
                    ->each(fn (Contact $contact) => $contact->delete());
            }

            $account->tokens()->delete();
            $account->notifications()->delete();
            $account->delete();
        });
    }

    /**
     * Nothing to reverse: the account is not meant to exist in production.
     */
    public function down(): void {}
};
