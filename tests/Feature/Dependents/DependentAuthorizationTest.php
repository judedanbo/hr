<?php

namespace Tests\Feature\Dependents;

use App\Models\Dependent;
use App\Models\InstitutionPerson;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A dependent may be added, changed or removed by the member of staff it
 * belongs to, or by someone holding update staff, and by nobody else who
 * merely happens to be signed in.
 */
class DependentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_add_a_dependent_to_another_member_of_staff(): void
    {
        $colleague = $this->activeStaff();

        $this->actingAs($this->staffUser())
            ->post(route('dependent.store'), $this->payload($colleague))
            ->assertForbidden();

        $this->assertSame(0, Dependent::where('staff_id', $colleague->id)->count());
        $this->assertDatabaseMissing('people', ['surname' => 'Mensah', 'first_name' => 'Esi']);
    }

    public function test_staff_can_add_their_own_dependent(): void
    {
        $user = $this->staffUser();
        $ownStaff = $this->staffRecordOf($user);

        $this->actingAs($user)
            ->post(route('dependent.store'), $this->payload($ownStaff))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $dependent = Dependent::where('staff_id', $ownStaff->id)->firstOrFail();
        $this->assertSame('Child', $dependent->relation);
        $this->assertSame('Esi', $dependent->person->first_name);
    }

    public function test_adding_a_dependent_for_a_staff_member_that_does_not_exist_is_refused(): void
    {
        $this->actingAs($this->staffUser())
            ->post(route('dependent.store'), array_merge($this->payload($this->activeStaff()), ['staff_id' => 999999]))
            ->assertForbidden();

        $this->assertSame(0, Dependent::count());
    }

    public function test_staff_cannot_update_another_staff_members_dependent(): void
    {
        $colleague = $this->activeStaff();
        $dependent = $this->dependentOf($colleague);

        $this->actingAs($this->staffUser())
            ->post(route('dependent.update', ['dependent' => $dependent->id]), $this->payload($colleague, ['relation' => 'Hijacked']))
            ->assertForbidden();

        $this->assertSame('Spouse', $dependent->fresh()->relation);
    }

    public function test_staff_can_update_their_own_dependent(): void
    {
        $user = $this->staffUser();
        $ownStaff = $this->staffRecordOf($user);
        $dependent = $this->dependentOf($ownStaff);

        $this->actingAs($user)
            ->post(route('dependent.update', ['dependent' => $dependent->id]), $this->payload($ownStaff, ['relation' => 'Partner']))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('Partner', $dependent->fresh()->relation);
    }

    public function test_staff_cannot_move_their_dependent_onto_another_staff_members_record(): void
    {
        $user = $this->staffUser();
        $ownStaff = $this->staffRecordOf($user);
        $colleague = $this->activeStaff();
        $dependent = $this->dependentOf($ownStaff);

        $this->actingAs($user)
            ->post(route('dependent.update', ['dependent' => $dependent->id]), $this->payload($colleague))
            ->assertForbidden();

        $this->assertSame($ownStaff->id, $dependent->fresh()->staff_id);
    }

    public function test_a_staff_id_that_only_looks_unchanged_cannot_move_a_dependent(): void
    {
        // A JSON true passes the integer rule and is stored as 1, so it names staff record 1.
        $colleague = InstitutionPerson::factory()->create(['id' => 1]);
        $user = $this->staffUser();
        $ownStaff = $this->staffRecordOf($user);
        $dependent = $this->dependentOf($ownStaff);

        $this->actingAs($user)
            ->postJson(route('dependent.update', ['dependent' => $dependent->id]), $this->payload($ownStaff, ['staff_id' => true]))
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson(route('dependent.store'), $this->payload($ownStaff, ['staff_id' => true]))
            ->assertForbidden();

        $this->assertSame($ownStaff->id, $dependent->fresh()->staff_id);
        $this->assertSame(0, Dependent::where('staff_id', $colleague->id)->count());
    }

    public function test_staff_can_update_their_own_dependent_when_the_staff_id_arrives_as_a_string(): void
    {
        $user = $this->staffUser();
        $ownStaff = $this->staffRecordOf($user);
        $dependent = $this->dependentOf($ownStaff);

        $this->actingAs($user)
            ->post(route('dependent.update', ['dependent' => $dependent->id]), $this->payload($ownStaff, [
                'staff_id' => (string) $ownStaff->id,
                'relation' => 'Partner',
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('Partner', $dependent->fresh()->relation);
        $this->assertSame($ownStaff->id, $dependent->fresh()->staff_id);
    }

    public function test_staff_cannot_delete_another_staff_members_dependent(): void
    {
        $dependent = $this->dependentOf($this->activeStaff());

        $this->actingAs($this->staffUser())
            ->delete(route('dependent.delete', ['dependent' => $dependent->id]))
            ->assertForbidden();

        $this->assertNotSoftDeleted('dependents', ['id' => $dependent->id]);
    }

    public function test_staff_can_delete_their_own_dependent(): void
    {
        $user = $this->staffUser();
        $dependent = $this->dependentOf($this->staffRecordOf($user));

        $this->actingAs($user)
            ->delete(route('dependent.delete', ['dependent' => $dependent->id]))
            ->assertRedirect();

        $this->assertSoftDeleted('dependents', ['id' => $dependent->id]);
    }

    public function test_user_with_update_staff_can_manage_another_staff_members_dependents(): void
    {
        $admin = User::factory()->create(['person_id' => $this->activeStaff()->person_id]);
        $admin->givePermissionTo('update staff');
        $colleague = $this->activeStaff();

        $this->actingAs($admin)
            ->post(route('dependent.store'), $this->payload($colleague))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $dependent = Dependent::where('staff_id', $colleague->id)->firstOrFail();

        $this->actingAs($admin)
            ->post(route('dependent.update', ['dependent' => $dependent->id]), $this->payload($colleague, ['relation' => 'Ward']))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('Ward', $dependent->fresh()->relation);

        $this->actingAs($admin)
            ->delete(route('dependent.delete', ['dependent' => $dependent->id]))
            ->assertRedirect();

        $this->assertSoftDeleted('dependents', ['id' => $dependent->id]);
    }

    public function test_self_service_dependent_permissions_do_not_reach_another_staff_members_dependents(): void
    {
        // StaffUserSeeder and DependentSeeder give the staff role these permissions,
        // so a database they have touched has every member of staff holding them.
        $user = $this->staffUser();
        $user->givePermissionTo(['create dependent', 'edit dependent', 'update dependent', 'delete dependent']);
        $colleague = $this->activeStaff();
        $dependent = $this->dependentOf($colleague);

        $this->actingAs($user)
            ->post(route('dependent.store'), $this->payload($colleague))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('dependent.update', ['dependent' => $dependent->id]), $this->payload($colleague, ['relation' => 'Hijacked']))
            ->assertForbidden();

        $this->actingAs($user)
            ->delete(route('dependent.delete', ['dependent' => $dependent->id]))
            ->assertForbidden();

        $this->assertSame('Spouse', $dependent->fresh()->relation);
        $this->assertSame(1, Dependent::where('staff_id', $colleague->id)->count());
    }

    public function test_user_with_no_linked_person_cannot_manage_dependents(): void
    {
        $user = User::factory()->create(['person_id' => null]);
        $user->assignRole('staff');
        $colleague = $this->activeStaff();
        $dependent = $this->dependentOf($colleague);

        $this->actingAs($user)
            ->post(route('dependent.store'), $this->payload($colleague))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('dependent.update', ['dependent' => $dependent->id]), $this->payload($colleague, ['relation' => 'Hijacked']))
            ->assertForbidden();

        $this->actingAs($user)
            ->delete(route('dependent.delete', ['dependent' => $dependent->id]))
            ->assertForbidden();

        $this->assertSame('Spouse', $dependent->fresh()->relation);
        $this->assertSame(1, Dependent::where('staff_id', $colleague->id)->count());
    }

    /**
     * A signed-in member of staff holding only the staff role.
     */
    private function staffUser(): User
    {
        $user = User::factory()->create(['person_id' => $this->activeStaff()->person_id]);
        $user->assignRole('staff');

        return $user;
    }

    private function staffRecordOf(User $user): InstitutionPerson
    {
        return InstitutionPerson::where('person_id', $user->person_id)->firstOrFail();
    }

    private function activeStaff(): InstitutionPerson
    {
        $staff = InstitutionPerson::factory()->create();
        $staff->statuses()->create([
            'status' => 'A',
            'start_date' => now()->subYear(),
            'institution_id' => $staff->institution_id,
        ]);

        return $staff;
    }

    private function dependentOf(InstitutionPerson $staff): Dependent
    {
        return Dependent::create([
            'staff_id' => $staff->id,
            'person_id' => Person::factory()->create()->id,
            'relation' => 'Spouse',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(InstitutionPerson $staff, array $overrides = []): array
    {
        return array_merge([
            'surname' => 'Mensah',
            'first_name' => 'Esi',
            'staff_id' => $staff->id,
            'relation' => 'Child',
        ], $overrides);
    }
}
