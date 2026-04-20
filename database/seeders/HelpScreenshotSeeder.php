<?php

namespace Database\Seeders;

use App\Enums\ContactTypeEnum;
use App\Models\Address;
use App\Models\Contact;
use App\Models\InstitutionPerson;
use App\Models\Person;
use App\Models\Qualification;
use App\Models\Status;
use App\Models\User;
use App\Notifications\PhotoApprovedNotification;
use App\Notifications\PhotoPendingApprovalNotification;
use App\Notifications\PhotoRejectedNotification;
use App\Notifications\QualificationPendingApprovalNotification;
use Illuminate\Database\Seeder;

class HelpScreenshotSeeder extends Seeder
{
    public function run(): void
    {
        $user = $this->createScreenshotUser();
        $person = $user->person;

        $this->seedActiveStatus($person);
        $this->seedQualifications($person);
        $this->seedNotifications($user, $person);
        $this->seedPendingPhoto($person);
        $this->seedPhotoApprovalQueue();
        $this->seedContacts($person);
        $this->seedAddress($person);
    }

    private function createScreenshotUser(): User
    {
        // Find an existing Person with a staff record, or create one
        $person = Person::whereHas('institution')->first();

        if (! $person) {
            $person = Person::factory()->create();
            InstitutionPerson::factory()->create(['person_id' => $person->id]);
        }

        $user = User::firstOrCreate(
            ['email' => 'screenshots@help.test'],
            [
                'name' => $person->full_name ?? 'Screenshot User',
                'person_id' => $person->id,
                'password' => bcrypt('screenshot-password'),
                'email_verified_at' => now(),
                'password_change_at' => now(),
            ]
        );

        // Ensure user is linked to the person
        if (! $user->person_id) {
            $user->update(['person_id' => $person->id]);
        }

        // Assign super-administrator role
        if (! $user->hasRole('super-administrator')) {
            $user->assignRole('super-administrator');
        }

        return $user->fresh('person');
    }

    private function seedNotifications(User $user, Person $person): void
    {
        // Clear existing notifications for this user
        $user->notifications()->delete();

        // Get a pending qualification for the notification
        $pendingQualification = $person->qualifications()->where('status', 'pending')->first()
            ?? $person->qualifications()->first();

        // Unread notifications
        $user->notify(new PhotoApprovedNotification($person));
        $user->notify(new QualificationPendingApprovalNotification($pendingQualification));

        // Mark older ones as read for a mix
        $user->notify(new PhotoRejectedNotification($person));
        $user->notify(new PhotoPendingApprovalNotification($person));

        // Mark the last two as read
        $user->notifications()
            ->latest()
            ->take(2)
            ->get()
            ->each(fn ($n) => $n->markAsRead());
    }

    private function seedPendingPhoto(Person $person): void
    {
        // Set a pending photo on the screenshot user's person
        $person->update([
            'pending_image' => 'photos/sample-pending.jpg',
            'pending_image_at' => now()->subHours(3),
        ]);
    }

    private function seedPhotoApprovalQueue(): void
    {
        // Set pending photos on 2 other staff for the approvals table
        $staffPersons = Person::whereHas('institution')
            ->where('id', '!=', User::where('email', 'screenshots@help.test')->value('person_id'))
            ->whereNull('pending_image')
            ->limit(2)
            ->get();

        foreach ($staffPersons as $person) {
            $person->update([
                'pending_image' => 'photos/sample-pending-' . $person->id . '.jpg',
                'pending_image_at' => now()->subHours(rand(1, 48)),
            ]);
        }
    }

    private function seedQualifications(Person $person): void
    {
        // Only add if person has fewer than 2 qualifications
        if ($person->qualifications()->count() >= 2) {
            return;
        }

        Qualification::factory()
            ->approved()
            ->create([
                'person_id' => $person->id,
                'qualification' => 'Bachelor of Science',
                'institution' => 'University of Ghana',
                'course' => 'Computer Science',
                'year' => '2015',
            ]);

        Qualification::factory()
            ->pending()
            ->create([
                'person_id' => $person->id,
                'qualification' => 'Master of Business Administration',
                'institution' => 'Ghana Institute of Management',
                'course' => 'Finance',
                'year' => '2020',
            ]);

        Qualification::factory()
            ->approved()
            ->create([
                'person_id' => $person->id,
                'qualification' => 'Professional Certificate',
                'institution' => 'ICAG',
                'course' => 'Accounting',
                'year' => '2022',
            ]);
    }

    private function seedActiveStatus(Person $person): void
    {
        $staff = InstitutionPerson::where('person_id', $person->id)->first();

        if (! $staff || $staff->statuses()->where('status', 'A')->whereNull('end_date')->exists()) {
            return;
        }

        Status::create([
            'staff_id' => $staff->id,
            'status' => 'A',
            'description' => 'Active',
            'start_date' => $staff->hire_date,
            'end_date' => null,
            'institution_id' => $staff->institution_id,
        ]);
    }

    private function seedContacts(Person $person): void
    {
        if ($person->contacts()->count() >= 2) {
            return;
        }

        Contact::firstOrCreate(
            ['person_id' => $person->id, 'contact_type' => ContactTypeEnum::PHONE],
            ['contact' => '0244123456', 'valid_end' => null]
        );

        Contact::firstOrCreate(
            ['person_id' => $person->id, 'contact_type' => ContactTypeEnum::EMAIL],
            ['contact' => 'staff.member@audit.gov.gh', 'valid_end' => null]
        );
    }

    private function seedAddress(Person $person): void
    {
        if ($person->address()->count() >= 1) {
            return;
        }

        $person->address()->create([
            'address_line_1' => '12 Independence Avenue',
            'city' => 'Accra',
            'region' => 'Greater Accra',
            'country' => 'Ghana',
            'post_code' => 'GA-100',
        ]);
    }
}
