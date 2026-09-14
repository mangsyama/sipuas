<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use App\Models\Role;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class StaffReviewQrTest extends TestCase
{
    use DatabaseMigrations;

    public function test_report_form_receives_staff_target_and_room(): void
    {
        $room = Room::updateOrCreate(
            ['name' => 'POLIKLINIK'],
            ['building_name' => 'Gedung B', 'location_floor' => 'Lantai 1', 'is_active' => true]
        );

        $response = $this->get('/report?room_id=POLIKLINIK&target=Ns.+Sinta+Dewi,+S.Kep');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Report/Create')
            ->where('targetObject', 'Ns. Sinta Dewi, S.Kep')
            ->where('roomId', (string) $room->id)
            ->where('initialStep', 2)
            ->where('reportMode', 'review')
        );
    }

    public function test_regular_report_url_does_not_activate_review_mode(): void
    {
        $response = $this->get('/report');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Report/Create')
            ->where('reportMode', '')
            ->where('targetObject', '')
            ->where('initialStep', null)
        );
    }

    public function test_submitting_report_with_staff_target(): void
    {
        $room = Room::updateOrCreate(
            ['name' => 'POLIKLINIK'],
            ['building_name' => 'Gedung B', 'location_floor' => 'Lantai 1', 'is_active' => true]
        );

        $response = $this->postJson('/report', [
            'unit_id' => $room->id,
            'target_object' => 'Ns. Sinta Dewi, S.Kep',
            'isi_laporan' => 'Pelayanan Ns. Sinta Dewi sangat ramah, sigap, dan komunikatif sekali.',
            'reporter_name' => 'Pasien Anonim',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('reports', [
            'room_id' => $room->id,
            'target_object' => 'Ns. Sinta Dewi, S.Kep',
        ]);
    }

    public function test_admin_qr_generator_includes_staff_users(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'ADMINISTRATOR']);
        $admin = User::firstOrCreate(
            ['username' => 'admin_test'],
            [
                'name' => 'Admin Test',
                'email' => 'admintest@sipuas.local',
                'password' => bcrypt('password'),
                'role_id' => $adminRole->id,
                'is_active' => true,
            ]
        );

        $response = $this->actingAs($admin)->get('/admin/qr-generator');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('QrGenerator/Index')
            ->has('rooms')
            ->has('staffUsers')
        );
    }
}
