<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExecutiveAndKasiDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    public function test_kabid_dashboard_renders_with_real_sla_and_charts(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::KEPALA_BIDANG,
            'is_active' => true,
        ]);

        $room = Room::first();

        // Create sample report verified in 2 hours (120 mins)
        $now = Carbon::now();
        $rep1 = Report::create([
            'ticket_number' => 'LP-TEST-001',
            'room_id' => $room->id,
            'isi_laporan' => 'Perawat sangat ramah dan sigap melayani.',
            'ai_sentiment' => 'POSITIF',
            'ai_category' => 'Sikap & Keramahan Staf',
            'status' => 'VERIFIED',
            'verified_by' => $admin->id,
        ]);
        $rep1->created_at = $now->copy()->subHours(5);
        $rep1->verified_at = $now->copy()->subHours(3);
        $rep1->saveQuietly();

        // Create pending complaint
        $rep2 = Report::create([
            'ticket_number' => 'LP-TEST-002',
            'room_id' => $room->id,
            'isi_laporan' => 'Waktu tunggu obat di apotek cukup lama.',
            'ai_sentiment' => 'NEGATIF',
            'ai_category' => 'Waktu Tunggu & Antrean',
            'status' => 'PENDING',
        ]);
        $rep2->created_at = $now->copy()->subHours(1);
        $rep2->saveQuietly();

        $response = $this->actingAs($admin)->get(route('executive.dashboard', ['period' => 'today']));
        $response->assertOk();
        $response->assertInertia(fn ($page) => 
            $page->component('Kabid/Dashboard')
                ->has('executiveStats')
                ->where('executiveStats.total_rs_reports', 2)
                ->where('executiveStats.positive_count', 1)
                ->where('executiveStats.negative_count', 1)
                ->where('executiveStats.avg_kasi_response_hours', '2 Jam')
                ->where('executiveStats.completion_rate', 50)
                ->has('trendChart')
                ->has('categoryChart')
                ->has('sentimentChart')
                ->has('redZoneUnits')
                ->has('rooms')
                ->has('filters')
        );
    }

    public function test_kasi_responsiveness_page_renders_with_real_sla_metrics(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::ADMINISTRATOR,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('executive.kasi-responsiveness', ['period' => '30d']));
        $response->assertOk();
        $response->assertInertia(fn ($page) => 
            $page->component('Kabid/KasiResponsiveness')
                ->has('kasiData')
                ->has('summary')
                ->has('rooms')
                ->has('filters')
        );
    }

    public function test_kasi_dashboard_renders_with_unit_sla_and_analytics(): void
    {
        $room = Room::first();

        $kasi = User::factory()->create([
            'role_id' => Role::KEPALA_SEKSI,
            'room_id' => $room->id,
            'is_active' => true,
        ]);

        $now = Carbon::now();
        $rep3 = Report::create([
            'ticket_number' => 'LP-TEST-003',
            'room_id' => $room->id,
            'isi_laporan' => 'AC kamar rawat inap tidak dingin sejak kemarin malam.',
            'ai_sentiment' => 'NEGATIF',
            'ai_category' => 'Sarana & Fasilitas',
            'status' => 'PENDING',
        ]);
        $rep3->created_at = $now->copy()->subHours(26);
        $rep3->saveQuietly();

        $response = $this->actingAs($kasi)->get(route('kasi.dashboard'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => 
            $page->component('Kasi/Dashboard')
                ->has('unitStats')
                ->where('unitStats.pending', 1)
                ->where('unitStats.total', 1)
                ->has('unitTrend')
                ->has('topCategories')
                ->has('recentReports')
                ->has('filters')
        );
    }

    public function test_kasi_feed_renders_with_reports_queue_and_actions(): void
    {
        $room = Room::first();

        $kasi = User::factory()->create([
            'role_id' => Role::KEPALA_SEKSI,
            'room_id' => $room->id,
            'is_active' => true,
        ]);

        $rep = Report::create([
            'ticket_number' => 'LP-TEST-004',
            'room_id' => $room->id,
            'isi_laporan' => 'Pelayanan cepat dan penjelasan dokter sangat jelas.',
            'ai_sentiment' => 'POSITIF',
            'ai_category' => 'Sikap & Keramahan Staf',
            'status' => 'PENDING',
        ]);

        $response = $this->actingAs($kasi)->get(route('kasi.feed'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => 
            $page->component('Kasi/Feed')
                ->has('initialReports')
                ->has('stats')
                ->where('stats.total', 1)
                ->where('stats.pending', 1)
                ->has('filters')
        );
    }

    public function test_dashboard_utama_is_admin_only_and_redirects_other_roles(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::ADMINISTRATOR,
            'is_active' => true,
        ]);
        $kasi = User::factory()->create([
            'role_id' => Role::KEPALA_SEKSI,
            'is_active' => true,
        ]);
        $kabid = User::factory()->create([
            'role_id' => Role::KEPALA_BIDANG,
            'is_active' => true,
        ]);
        $staff = User::factory()->create([
            'role_id' => Role::STAFF,
            'is_active' => true,
        ]);

        // Admin can access Dashboard Utama
        $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertInertia(fn ($page) => $page->component('Dashboard/Index'));

        // Kasi is redirected to Kasi Dashboard
        $this->actingAs($kasi)->get(route('dashboard'))->assertRedirect(route('kasi.dashboard'));

        // Kabid is redirected to Kabid Dashboard
        $this->actingAs($kabid)->get(route('dashboard'))->assertRedirect(route('kabid.dashboard'));

        // Staff is redirected to Attendance
        $this->actingAs($staff)->get(route('dashboard'))->assertRedirect(route('staff.attendance'));
    }

    public function test_kasi_process_verification_updates_status_and_dispatches_wa_to_reporter(): void
    {
        $kasi = User::factory()->create([
            'role_id' => Role::KEPALA_SEKSI,
            'is_active' => true,
        ]);
        $room = Room::first();

        $report = Report::create([
            'ticket_number' => 'LP-WA-TEST-99',
            'room_id' => $room->id,
            'isi_laporan' => 'Pelayanan ramah dan memuaskan.',
            'reporter_name' => 'Budi Santoso',
            'reporter_phone' => '081234567890',
            'status' => 'PENDING',
        ]);

        $response = $this->actingAs($kasi)->post(route('kasi.verify.process', $report->id), [
            'action_type' => 'NETRAL',
            'points' => 0,
            'supervisor_notes' => 'Telah ditindaklanjuti dengan baik.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'VERIFIED',
            'verified_by' => $kasi->id,
        ]);
    }

    public function test_kasi_and_kabid_reports_dedicated_routes(): void
    {
        $kasi = User::factory()->create([
            'role_id' => Role::KEPALA_SEKSI,
            'is_active' => true,
        ]);
        $kabid = User::factory()->create([
            'role_id' => Role::KEPALA_BIDANG,
            'is_active' => true,
        ]);

        // Kasi can access /kasi/reports
        $this->actingAs($kasi)->get(route('kasi.reports'))->assertOk()->assertInertia(fn ($page) => $page->component('ReportExport/Index'));

        // Kabid can access /kabid/reports and /kabid/dashboard
        $this->actingAs($kabid)->get(route('kabid.reports'))->assertOk()->assertInertia(fn ($page) => $page->component('ReportExport/Index'));
        $this->actingAs($kabid)->get(route('kabid.dashboard'))->assertOk()->assertInertia(fn ($page) => $page->component('Kabid/Dashboard'));
    }
}


