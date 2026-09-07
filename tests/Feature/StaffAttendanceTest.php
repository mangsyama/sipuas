<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\StaffAttendance;
use App\Models\User;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    public function test_staff_can_view_live_attendance_screen(): void
    {
        $unit = Unit::first() ?? Unit::create(['name' => 'Instalasi Radiologi', 'is_active' => true]);
        $user = User::factory()->create([
            'role_id' => Role::STAFF,
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/staff/attendance');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Staff/Attendance')
                ->has('staff')
                ->has('recentAttendances')
        );
    }

    public function test_staff_can_clock_in(): void
    {
        $unit = Unit::first() ?? Unit::create(['name' => 'Instalasi Radiologi', 'is_active' => true]);
        $user = User::factory()->create([
            'role_id' => Role::STAFF,
            'unit_id' => $unit->id,
            'is_active' => true,
            'is_on_duty' => false,
        ]);

        $response = $this->actingAs($user)->post('/attendance/check-in', [
            'shift_name' => 'PAGI',
            'notes' => 'Siap bertugas',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('staff_attendances', [
            'user_id' => $user->id,
            'shift_name' => 'PAGI',
            'status' => 'ON_DUTY',
        ]);
        $this->assertTrue($user->fresh()->is_on_duty);
    }

    public function test_staff_can_clock_out(): void
    {
        $unit = Unit::first() ?? Unit::create(['name' => 'Instalasi Radiologi', 'is_active' => true]);
        $user = User::factory()->create([
            'role_id' => Role::STAFF,
            'unit_id' => $unit->id,
            'is_active' => true,
            'is_on_duty' => true,
        ]);

        StaffAttendance::create([
            'user_id' => $user->id,
            'unit_id' => $unit->id,
            'duty_date' => now()->toDateString(),
            'shift_name' => 'PAGI',
            'check_in_at' => now()->subHours(6),
            'status' => 'ON_DUTY',
        ]);

        $response = $this->actingAs($user)->post('/attendance/check-out');

        $response->assertRedirect();
        $this->assertDatabaseHas('staff_attendances', [
            'user_id' => $user->id,
            'status' => 'COMPLETED',
        ]);
        $this->assertFalse($user->fresh()->is_on_duty);
    }
}
