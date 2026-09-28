<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCreationValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->room = Room::first() ?? Room::create([
            'name' => 'Instalasi Gawat Darurat',
            'building_name' => 'Gedung A',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'role_id' => Role::ADMINISTRATOR,
            'is_active' => true,
            'approved_at' => now(),
        ]);
    }

    public function test_admin_can_create_user_with_valid_data(): void
    {
        $response = $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'Dr. Andi Pratama, Sp.A',
            'nip' => '198501012010011005',
            'username' => 'andipratama',
            'email' => 'andi@rs.local',
            'phone_number' => '081234567890',
            'role' => 'STAFF',
            'unit_id' => $this->room->id,
            'password' => 'secret123',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'name' => 'Dr. Andi Pratama, Sp.A',
            'nip' => '198501012010011005',
            'username' => 'andipratama',
            'email' => 'andi@rs.local',
            'phone_number' => '081234567890',
            'role_id' => Role::STAFF,
            'room_id' => $this->room->id,
            'is_active' => true,
        ]);
    }

    public function test_all_fields_are_strictly_required(): void
    {
        $response = $this->actingAs($this->admin)->post(route('users.store'), []);

        $response->assertSessionHasErrors([
            'name',
            'nip',
            'username',
            'email',
            'phone_number',
            'role',
            'unit_id',
            'password',
        ]);
    }

    public function test_nip_must_be_strictly_18_digits(): void
    {
        // Too short (10 digits)
        $responseShort = $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'Budi Santoso',
            'nip' => '1234567890',
            'username' => 'budis',
            'email' => 'budi@rs.local',
            'phone_number' => '081234567890',
            'role' => 'STAFF',
            'unit_id' => $this->room->id,
            'password' => 'secret123',
        ]);
        $responseShort->assertSessionHasErrors(['nip']);

        // Non-numeric
        $responseLetters = $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'Budi Santoso',
            'nip' => '19850101201001100A',
            'username' => 'budis2',
            'email' => 'budi2@rs.local',
            'phone_number' => '081234567890',
            'role' => 'STAFF',
            'unit_id' => $this->room->id,
            'password' => 'secret123',
        ]);
        $responseLetters->assertSessionHasErrors(['nip']);
    }

    public function test_phone_number_must_be_numeric_and_valid_length(): void
    {
        // Too short (< 10 digits)
        $responseShort = $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'Budi Santoso',
            'nip' => '198501012010011005',
            'username' => 'budis',
            'email' => 'budi@rs.local',
            'phone_number' => '081234',
            'role' => 'STAFF',
            'unit_id' => $this->room->id,
            'password' => 'secret123',
        ]);
        $responseShort->assertSessionHasErrors(['phone_number']);

        // Non-numeric
        $responseLetters = $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'Budi Santoso',
            'nip' => '198501012010011005',
            'username' => 'budis',
            'email' => 'budi@rs.local',
            'phone_number' => '0812345ABCD',
            'role' => 'STAFF',
            'unit_id' => $this->room->id,
            'password' => 'secret123',
        ]);
        $responseLetters->assertSessionHasErrors(['phone_number']);
    }
}
