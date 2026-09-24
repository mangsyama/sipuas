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

    public function test_kasi_can_cancel_report_without_penalizing_staff(): void
    {
        $kasi = User::factory()->create([
            'role_id' => Role::KEPALA_SEKSI,
            'is_active' => true,
        ]);
        $staff = User::factory()->create([
            'role_id' => Role::STAFF,
            'is_active' => true,
            'total_points' => 100,
        ]);
        $room = Room::first();

        $report = Report::create([
            'ticket_number' => 'LP-CANCEL-TEST-01',
            'room_id' => $room->id,
            'isi_laporan' => 'Pelayanan perawat lambat dan tidak ramah.',
            'reporter_name' => 'Warga Kritis',
            'reporter_phone' => '081987654321',
            'status' => 'PENDING',
        ]);

        // Process as DIBATALKAN
        $response = $this->actingAs($kasi)->post(route('kasi.verify.process', $report->id), [
            'action_type' => 'DIBATALKAN',
            'points' => 0,
            'supervisor_notes' => 'Setelah dicek langsung di CCTV dan klarifikasi di ruangan, perawat sudah melayani sesuai SOP dan antrean saat itu normal.',
            'selected_staff_ids' => [$staff->id], // even if submitted, must not execute staff
        ]);

        $response->assertRedirect();

        // 1. Report is marked VERIFIED with internal resolution_notes DIBATALKAN
        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'VERIFIED',
            'resolution_notes' => 'DIBATALKAN',
            'verified_by' => $kasi->id,
        ]);

        // 2. No staff is linked in report_staff and staff_kpi_logs
        $this->assertDatabaseMissing('report_staff', [
            'report_id' => $report->id,
        ]);
        $this->assertDatabaseMissing('staff_kpi_logs', [
            'report_id' => $report->id,
        ]);

        // 3. Staff points remain 100 (untouched)
        $this->assertEquals(100, $staff->fresh()->total_points);

        // 4. Verify page shows DIBATALKAN verified_action_type
        $this->actingAs($kasi)->get(route('kasi.verify', $report->ticket_number))
            ->assertOk()
            ->assertInertia(fn ($page) => 
                $page->where('reportDetail.verified_action_type', 'DIBATALKAN')
                     ->where('reportDetail.status', 'VERIFIED')
            );
    }

    public function test_kasi_process_verification_requires_supervisor_notes_for_all_actions(): void
    {
        $kasi = User::factory()->create([
            'role_id' => Role::KEPALA_SEKSI,
            'is_active' => true,
        ]);
        $room = Room::first();

        $report = Report::create([
            'ticket_number' => 'LP-NOTES-REQ-01',
            'room_id' => $room->id,
            'isi_laporan' => 'Uji validasi catatan berita acara wajib diisi.',
            'status' => 'PENDING',
        ]);

        // Attempt submit without supervisor_notes
        $response = $this->actingAs($kasi)->post(route('kasi.verify.process', $report->id), [
            'action_type' => 'NETRAL',
            'points' => 0,
            'supervisor_notes' => '',
        ]);

        $response->assertSessionHasErrors('supervisor_notes');
    }

    public function test_kasi_can_verify_report_with_hospital_kpi_category_and_severity(): void
    {
        $kasi = User::factory()->create([
            'role_id' => Role::KEPALA_SEKSI,
            'is_active' => true,
        ]);
        $room = Room::first();
        $staff = User::factory()->create([
            'role_id' => Role::STAFF,
            'room_id' => $room->id,
            'total_points' => 100,
            'is_active' => true,
        ]);

        $report = Report::create([
            'ticket_number' => 'LP-KPI-CAT-01',
            'room_id' => $room->id,
            'isi_laporan' => 'Petugas judes dan tidak ramah saat melayani administrasi.',
            'status' => 'PENDING',
        ]);

        $response = $this->actingAs($kasi)->post(route('kasi.verify.process', $report->id), [
            'action_type' => 'PEMOTONGAN',
            'kpi_category' => 'KERAMAHAN',
            'severity_level' => 'BERAT',
            'points' => 10,
            'supervisor_notes' => 'Telah dilakukan pembinaan terkait etika komunikasi 5S.',
            'selected_staff_ids' => [$staff->id],
        ]);

        $response->assertRedirect();

        // 1. Report updated with verified_kpi_category and verified_severity_level
        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'VERIFIED',
            'verified_kpi_category' => 'KERAMAHAN',
            'verified_severity_level' => 'BERAT',
        ]);

        // 2. ReportStaff has kpi_category, severity_level, and points
        $this->assertDatabaseHas('report_staff', [
            'report_id' => $report->id,
            'user_id' => $staff->id,
            'action_type' => 'PEMOTONGAN',
            'kpi_category' => 'KERAMAHAN',
            'severity_level' => 'BERAT',
            'points' => -10,
        ]);

        // 3. StaffKpiLog has kpi_category, severity_level, and points
        $this->assertDatabaseHas('staff_kpi_logs', [
            'report_id' => $report->id,
            'user_id' => $staff->id,
            'action_type' => 'PEMOTONGAN',
            'kpi_category' => 'KERAMAHAN',
            'severity_level' => 'BERAT',
            'points' => -10,
        ]);

        // 4. Staff total points decremented by 10
        $this->assertEquals(90, $staff->fresh()->total_points);

        // 5. Verify page exposes verified_kpi_category and verified_severity_level
        $this->actingAs($kasi)->get(route('kasi.verify', $report->ticket_number))
            ->assertOk()
            ->assertInertia(fn ($page) => 
                $page->where('reportDetail.verified_kpi_category', 'KERAMAHAN')
                     ->where('reportDetail.verified_severity_level', 'BERAT')
                     ->where('reportDetail.status', 'VERIFIED')
            );
    }

    public function test_kasi_verification_requires_kpi_category_for_penalty_and_reward(): void
    {
        $kasi = User::factory()->create([
            'role_id' => Role::KEPALA_SEKSI,
            'is_active' => true,
        ]);
        $room = Room::first();
        $staff = User::factory()->create([
            'role_id' => Role::STAFF,
            'room_id' => $room->id,
            'is_active' => true,
        ]);

        $report = Report::create([
            'ticket_number' => 'LP-KPI-VAL-01',
            'room_id' => $room->id,
            'isi_laporan' => 'Perawat sangat ramah dan menenangkan pasien.',
            'status' => 'PENDING',
        ]);

        // Attempt submit PENAMBAHAN without kpi_category
        $response = $this->actingAs($kasi)->post(route('kasi.verify.process', $report->id), [
            'action_type' => 'PENAMBAHAN',
            'points' => 5,
            'supervisor_notes' => 'Diberikan reward apresiasi pelayanan prima.',
            'selected_staff_ids' => [$staff->id],
        ]);

        $response->assertSessionHasErrors('kpi_category');
    }
}


