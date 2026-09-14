<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountActivationCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_user_can_view_activation_notice_page(): void
    {
        $room = Room::first() ?? Room::create(['name' => 'Poli Test', 'building_name' => 'Gedung A', 'is_active' => true]);

        $user = User::create([
            'name' => 'Perawat Test',
            'username' => 'perawat_test',
            'nip' => '199001012020011001',
            'email' => 'perawat@example.com',
            'password' => bcrypt('password'),
            'role_id' => Role::STAFF,
            'room_id' => $room->id,
            'phone_number' => '081234567890',
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)->get(route('activation.notice'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/Activation')
            ->where('user.is_active', false)
            ->where('user.has_requested', true)
        );
    }

    public function test_once_user_is_active_checking_activation_notice_redirects_into_the_app(): void
    {
        $room = Room::first() ?? Room::create(['name' => 'Poli Test', 'building_name' => 'Gedung B', 'is_active' => true]);

        $user = User::create([
            'name' => 'Staff Aktif',
            'username' => 'staff_aktif',
            'nip' => '199001012020011002',
            'email' => 'staff_aktif@example.com',
            'password' => bcrypt('password'),
            'role_id' => Role::STAFF,
            'room_id' => $room->id,
            'phone_number' => '081234567891',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('activation.notice'));

        // Since user is active and staff, they are automatically redirected to attendance
        $response->assertRedirect(route('staff.attendance'));
    }
}
