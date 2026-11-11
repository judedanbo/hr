<?php

namespace Tests\Feature\Auth;

use App\Enums\ContactTypeEnum;
use App\Http\Requests\Auth\RegisterRequest;
use App\Mail\UserCreated;
use App\Models\Contact;
use App\Models\InstitutionPerson;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_cannot_be_rendered_to_unauthenticated_user()
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_staff_can_register_with_the_official_email_on_their_staff_record(): void
    {
        Mail::fake();

        $staff = $this->staffMember('Ama', 'Mensah', 'GAS10001');
        $this->recordEmail($staff, 'ama.mensah@audit.gov.gh');

        $this->post('/register', $this->payload($staff, 'ama.mensah'))
            ->assertRedirect(route('login'))
            ->assertSessionHasNoErrors();

        $user = User::where('email', 'ama.mensah@audit.gov.gh')->first();

        $this->assertNotNull($user);
        $this->assertSame($staff->person_id, $user->person_id);
        $this->assertTrue($user->hasRole('staff'));
        $this->assertGuest();

        Mail::assertSent(UserCreated::class, 1);
        Mail::assertSent(
            UserCreated::class,
            fn (UserCreated $mail): bool => $mail->hasTo('ama.mensah@audit.gov.gh')
                && Hash::check($mail->password, $user->password)
        );

        // The mailbox was already on the staff record, so registering must not add it again.
        $this->assertSame(1, Contact::where('person_id', $staff->person_id)->count());
    }

    public function test_the_password_email_goes_only_to_the_recorded_mailbox(): void
    {
        $staff = $this->staffMember('Ama', 'Mensah', 'GAS10001');
        $this->recordEmail($staff, 'ama.mensah@audit.gov.gh');

        $this->post('/register', $this->payload($staff, 'ama.mensah'))
            ->assertRedirect(route('login'));

        $messages = Mail::mailer()->getSymfonyTransport()->messages();

        $this->assertCount(1, $messages);
        $this->assertSame(
            ['ama.mensah@audit.gov.gh'],
            array_map(fn ($address): string => $address->getAddress(), $messages->first()->getEnvelope()->getRecipients())
        );
    }

    public function test_the_recorded_mailbox_is_matched_regardless_of_case_or_a_typed_domain(): void
    {
        Mail::fake();

        $staff = $this->staffMember('Ama', 'Mensah', 'GAS10001');
        $this->recordEmail($staff, 'Ama.Mensah@AUDIT.GOV.GH');

        $this->post('/register', $this->payload($staff, 'AMA.Mensah@audit.gov.gh'))
            ->assertRedirect(route('login'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'person_id' => $staff->person_id,
            'email' => 'ama.mensah@audit.gov.gh',
        ]);
        Mail::assertSent(UserCreated::class, fn (UserCreated $mail): bool => $mail->hasTo('ama.mensah@audit.gov.gh'));
    }

    public function test_a_second_registration_for_the_same_person_is_refused(): void
    {
        Mail::fake();

        $staff = $this->staffMember('Ama', 'Mensah', 'GAS10001');
        $this->recordEmail($staff, 'ama.mensah@audit.gov.gh');
        $this->recordEmail($staff, 'a.mensah@audit.gov.gh');

        $this->post('/register', $this->payload($staff, 'ama.mensah'))
            ->assertSessionHasNoErrors();

        // A different recorded mailbox, so the unique users.email check cannot be
        // what refuses this: only the one-login-per-person guard can.
        $this->post('/register', $this->payload($staff, 'a.mensah'))
            ->assertSessionHasErrors(['staff_number' => 'User account already exists']);

        $this->assertSame(1, User::where('person_id', $staff->person_id)->count());
        $this->assertDatabaseMissing('users', ['email' => 'a.mensah@audit.gov.gh']);
        Mail::assertSent(UserCreated::class, 1);
    }

    public function test_a_login_created_while_a_registration_is_in_flight_is_not_duplicated(): void
    {
        Mail::fake();

        $staff = $this->staffMember('Ama', 'Mensah', 'GAS10001');
        $this->recordEmail($staff, 'ama.mensah@audit.gov.gh');
        $this->recordEmail($staff, 'a.mensah@audit.gov.gh');

        // Runs once RegisterRequest has validated and before the controller creates
        // the login, which is where an overlapping registration for the same person
        // would have landed its own.
        $this->app->afterResolving(RegisterRequest::class, function () use ($staff): void {
            User::factory()->create([
                'person_id' => $staff->person_id,
                'email' => 'a.mensah@audit.gov.gh',
            ]);
        });

        $this->post('/register', $this->payload($staff, 'ama.mensah'))
            ->assertSessionHasErrors(['staff_number' => 'User account already exists']);

        $this->assertSame(1, User::where('person_id', $staff->person_id)->count());
        $this->assertDatabaseMissing('users', ['email' => 'ama.mensah@audit.gov.gh']);
        Mail::assertNothingSent();
    }

    public function test_registration_is_refused_when_the_person_already_has_a_login(): void
    {
        Mail::fake();

        $staff = $this->staffMember('Ama', 'Mensah', 'GAS10001');
        $this->recordEmail($staff, 'ama.mensah@audit.gov.gh');
        User::factory()->create([
            'person_id' => $staff->person_id,
            'email' => 'created.by.admin@audit.gov.gh',
        ]);

        $this->post('/register', $this->payload($staff, 'ama.mensah'))
            ->assertSessionHasErrors(['staff_number' => 'User account already exists']);

        $this->assertSame(1, User::where('person_id', $staff->person_id)->count());
        Mail::assertNothingSent();
    }

    public function test_registration_is_refused_for_a_mailbox_that_is_not_on_the_staff_record(): void
    {
        Mail::fake();

        $staff = $this->staffMember('Ama', 'Mensah', 'GAS10001');
        $this->recordEmail($staff, 'ama.mensah@audit.gov.gh');

        $this->post('/register', $this->payload($staff, 'kofi.intruder'))
            ->assertSessionHasErrors(['email' => RegisterRequest::EMAIL_NOT_ON_RECORD]);

        $this->assertDatabaseMissing('users', ['person_id' => $staff->person_id]);
        $this->assertDatabaseMissing('users', ['email' => 'kofi.intruder@audit.gov.gh']);
        $this->assertDatabaseMissing('contacts', ['contact' => 'kofi.intruder@audit.gov.gh']);
        Mail::assertNothingSent();
    }

    public function test_a_mailbox_recorded_for_another_member_of_staff_cannot_be_used(): void
    {
        Mail::fake();

        $colleague = $this->staffMember('Ama', 'Mensah', 'GAS10001');
        $intruder = $this->staffMember('Kofi', 'Boateng', 'GAS10002');
        $this->recordEmail($intruder, 'kofi.boateng@audit.gov.gh');

        // Everything the form asks about the colleague is right; only the mailbox is the intruder's.
        $this->post('/register', $this->payload($colleague, 'kofi.boateng'))
            ->assertSessionHasErrors(['email' => RegisterRequest::EMAIL_NOT_ON_RECORD]);

        $this->assertDatabaseMissing('users', ['person_id' => $colleague->person_id]);
        $this->assertDatabaseMissing('users', ['email' => 'kofi.boateng@audit.gov.gh']);
        $this->assertDatabaseMissing('contacts', ['person_id' => $colleague->person_id]);
        Mail::assertNothingSent();
    }

    public function test_registration_is_refused_when_the_staff_record_has_no_official_email(): void
    {
        Mail::fake();

        $staff = $this->staffMember('Ama', 'Mensah', 'GAS10001');
        Contact::create([
            'person_id' => $staff->person_id,
            'contact_type' => ContactTypeEnum::PHONE,
            'contact' => '0244000001',
        ]);
        // A personal mailbox with the same local part is not an official one.
        $this->recordEmail($staff, 'ama.mensah@gmail.com');

        $this->post('/register', $this->payload($staff, 'ama.mensah'))
            ->assertSessionHasErrors(['email' => RegisterRequest::EMAIL_NOT_ON_RECORD]);

        $this->assertDatabaseMissing('users', ['person_id' => $staff->person_id]);
        Mail::assertNothingSent();
    }

    public function test_an_official_email_that_is_no_longer_valid_is_not_accepted(): void
    {
        Mail::fake();

        $staff = $this->staffMember('Ama', 'Mensah', 'GAS10001');
        $this->recordEmail($staff, 'ama.mensah@audit.gov.gh', ['valid_end' => now()->subMonth()]);

        $this->post('/register', $this->payload($staff, 'ama.mensah'))
            ->assertSessionHasErrors(['email' => RegisterRequest::EMAIL_NOT_ON_RECORD]);

        $this->assertDatabaseMissing('users', ['person_id' => $staff->person_id]);
        Mail::assertNothingSent();
    }

    public function test_registration_is_refused_when_the_names_do_not_match_the_staff_record(): void
    {
        Mail::fake();

        $staff = $this->staffMember('Ama', 'Mensah', 'GAS10001');
        $this->recordEmail($staff, 'ama.mensah@audit.gov.gh');

        $this->post('/register', array_merge($this->payload($staff, 'ama.mensah'), ['surname' => 'Owusu']))
            ->assertSessionHasErrors(['staff_number' => 'Staff number does not match the name']);

        $this->assertDatabaseMissing('users', ['person_id' => $staff->person_id]);
        Mail::assertNothingSent();
    }

    // public function test_can_register_a_new_users()
    // {
    //     $user = User::factory()->create();
    //     $response = $this->post('/login', [
    //         'email' => $user->email,
    //         'password' => 'password',
    //     ]);
    //     $this->assertAuthenticated();

    //     // $loginResponse = $this->post('/register', [
    //     //     'name' => 'Test User',
    //     //     'email' => 'test@example.com',
    //     //     'password' => 'password',
    //     //     'password_confirmation' => 'password',
    //     // ]);
    //     $loginResponse->assertRedirect(RouteServiceProvider::HOME);
    // }

    private function staffMember(string $firstName, string $surname, string $staffNumber): InstitutionPerson
    {
        $person = Person::factory()->create([
            'first_name' => $firstName,
            'surname' => $surname,
        ]);

        return InstitutionPerson::factory()->create([
            'person_id' => $person->id,
            'staff_number' => $staffNumber,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function recordEmail(InstitutionPerson $staff, string $email, array $attributes = []): Contact
    {
        return Contact::create(array_merge([
            'person_id' => $staff->person_id,
            'contact_type' => ContactTypeEnum::EMAIL,
            'contact' => $email,
        ], $attributes));
    }

    /**
     * @return array<string, string>
     */
    private function payload(InstitutionPerson $staff, string $email): array
    {
        return [
            'staff_number' => $staff->staff_number,
            'surname' => $staff->person->surname,
            'first_name' => $staff->person->first_name,
            'email' => $email,
        ];
    }
}
