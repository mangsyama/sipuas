<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested_via_whatsapp(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $user = User::factory()->create([
            'phone_number' => '081234567890',
            'is_active' => true,
        ]);

        $response = $this->post('/forgot-password', [
            'account' => $user->username,
        ]);

        $response->assertSessionHas('status');
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $user = User::factory()->create([
            'phone_number' => '081234567890',
            'is_active' => true,
        ]);

        $token = Password::createToken($user);

        $response = $this->get('/reset-password/' . $token . '?email=' . urlencode($user->email));

        $response->assertStatus(200);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $user = User::factory()->create([
            'phone_number' => '081234567890',
            'is_active' => true,
        ]);

        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }
}
