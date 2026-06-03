<?php

namespace Tests\Feature\Leave;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveRequestAdminControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $guestUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create(['password_change_at' => now()]);
        $this->superAdmin->assignRole('super-administrator');

        $this->guestUser = User::factory()->create(['password_change_at' => now()]);
    }

    public function test_index_requires_permission(): void
    {
        $this->actingAs($this->guestUser)->get(route('leave-requests.index'))->assertForbidden();
    }

    public function test_index_lists_requests(): void
    {
        LeaveRequest::factory()->count(2)->create();

        $this->actingAs($this->superAdmin)
            ->get(route('leave-requests.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('LeaveRequest/All')->has('requests.data', 2));
    }

    public function test_show_displays_a_request(): void
    {
        $leaveRequest = LeaveRequest::factory()->create();

        $this->actingAs($this->superAdmin)
            ->get(route('leave-requests.show', $leaveRequest))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('LeaveRequest/AdminShow')->where('request.id', $leaveRequest->id));
    }
}
