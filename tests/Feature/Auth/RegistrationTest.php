<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_cannot_be_rendered_to_unauthenticated_user()
    {
        $response = $this->get('/register');

        $response->assertStatus(404);
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
    //     // dd($loginResponse);

    //     $loginResponse->assertRedirect(RouteServiceProvider::HOME);
    // }
}
