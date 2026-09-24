<?php

namespace Tests\Feature;

use App\Events\NewReportSubmitted;
use App\Events\NewUserRegistered;
use App\Models\Report;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    public function test_kasi_and_admin_receive_pending_reports_count_and_notifications(): void
    {
        $room = Room::first();

        // Create 2 pending reports
        Report::create([
            'ticket_number' => 'LP-TEST-001',
            'room_id' => $room->id,
            'isi_laporan' => 'Pelayanan antrean lambat',
            'status' => 'PENDING',
        ]);
        Report::create([
            'ticket_number' => 'LP-TEST-002',
            'room_id' => $room->id,
            'isi_laporan' => 'Dokter sangat ramah dan baik',
            'status' => 'PENDING',
        ]);

        // Create 1 pending user registration
        User::factory()->create([
            'name' => 'Calon Staf Baru',
            'nip' => '199501012022011001',
            'is_active' => false,
        ]);

        $admin = User::where('role_id', Role::ADMINISTRATOR)->first();
        $kasi = User::factory()->create([
            'role_id' => Role::KEPALA_SEKSI,
            'room_id' => null, // Untied Kasi
            'is_active' => true,
        ]);

        // Test Admin can see both pending reports and pending user approvals
        $responseAdmin = $this->actingAs($admin)->get(route('dashboard'));
        $responseAdmin->assertOk();
        $responseAdmin->assertInertia(fn ($page) => 
            $page->where('auth.pending_reports_count', 2)
                 ->where('auth.pending_approvals_count', 1)
                 ->has('notifications', 3)
                 ->where('unread_notifications_count', 3)
        );

        // Test Kasi sees pending reports (2 items)
        $responseKasi = $this->actingAs($kasi)->get(route('kasi.dashboard'));
        $responseKasi->assertOk();
        $responseKasi->assertInertia(fn ($page) => 
            $page->where('auth.pending_reports_count', 2)
                 ->has('notifications', 2) // Kasi only sees reports, not user approvals
                 ->where('unread_notifications_count', 2)
        );
    }

    public function test_mark_as_read_persists_in_session(): void
    {
        $admin = User::where('role_id', Role::ADMINISTRATOR)->first();

        $response = $this->actingAs($admin)->post(route('notifications.markAsRead', 'report-1'));
        $response->assertJson(['success' => true]);
        $response->assertSessionHas('read_notifications', ['report-1']);

        $responseAll = $this->actingAs($admin)->post(route('notifications.markAllAsRead'), [
            'ids' => ['report-2', 'user-1'],
        ]);
        $responseAll->assertJson(['success' => true]);
        $responseAll->assertSessionHas('read_notifications', ['report-1', 'report-2', 'user-1']);
    }

    public function test_new_report_broadcast_event_dispatched_on_store(): void
    {
        Event::fake([NewReportSubmitted::class]);

        $room = Room::first();

        $response = $this->post(route('report.store'), [
            'room_id' => $room->id,
            'isi_laporan' => 'Pujian pelayanan dokter poli anak sangat memuaskan.',
            'reporter_name' => 'Wayan Sukerta',
            'reporter_phone' => '081234567890',
        ]);

        $response->assertRedirectContains('/report/success');
        Event::assertDispatched(NewReportSubmitted::class);
    }

    public function test_visiting_kasi_verify_automatically_marks_report_notification_as_read(): void
    {
        $room = Room::first();
        $report = Report::create([
            'ticket_number' => 'LP-AUTO-READ',
            'room_id' => $room->id,
            'isi_laporan' => 'Pelayanan AC ruangan poli anak mati.',
            'status' => 'PENDING',
        ]);

        $kasi = User::factory()->create([
            'role_id' => Role::KEPALA_SEKSI,
            'room_id' => null,
            'is_active' => true,
        ]);

        // Before visiting verify, unread count is 1
        $dashRes = $this->actingAs($kasi)->get(route('kasi.dashboard'));
        $dashRes->assertOk();
        $dashRes->assertInertia(fn ($page) => 
            $page->where('unread_notifications_count', 1)
        );

        // Visit verify page for this report
        $verifyRes = $this->actingAs($kasi)->get(route('kasi.verify', $report->ticket_number));
        $verifyRes->assertOk();
        $verifyRes->assertSessionHas('read_notifications', ['report-' . $report->id]);
        $verifyRes->assertInertia(fn ($page) => 
            $page->where('unread_notifications_count', 0)
        );
    }

    public function test_visiting_user_approval_show_automatically_marks_user_notification_as_read(): void
    {
        $pendingUser = User::factory()->create([
            'name' => 'Calon Verifikasi Auto Read',
            'nip' => '199001012020011005',
            'is_active' => false,
        ]);

        $admin = User::where('role_id', Role::ADMINISTRATOR)->first();

        // Before visiting show, unread count is 1
        $dashRes = $this->actingAs($admin)->get(route('dashboard'));
        $dashRes->assertOk();
        $dashRes->assertInertia(fn ($page) => 
            $page->where('unread_notifications_count', 1)
        );

        // Visit approval show page
        $showRes = $this->actingAs($admin)->get(route('users.approvals.show', $pendingUser->id));
        $showRes->assertOk();
        $showRes->assertSessionHas('read_notifications', ['user-' . $pendingUser->id]);
        $showRes->assertInertia(fn ($page) => 
            $page->where('unread_notifications_count', 0)
        );
    }

    public function test_mark_all_as_read_synchronizes_sidebar_pending_reports_and_approvals_count_to_zero(): void
    {
        $room = Room::first();
        $report = Report::create([
            'ticket_number' => 'LP-SYNC-001',
            'room_id' => $room->id,
            'isi_laporan' => 'Kipas angin ruangan rusak.',
            'status' => 'PENDING',
        ]);

        $pendingUser = User::factory()->create([
            'name' => 'Calon Staf Sync',
            'nip' => '199201012022011009',
            'is_active' => false,
        ]);

        $admin = User::where('role_id', Role::ADMINISTRATOR)->first();

        // Check before: both pending counts and unread count are > 0
        $dashRes = $this->actingAs($admin)->get(route('dashboard'));
        $dashRes->assertOk();
        $dashRes->assertInertia(fn ($page) => 
            $page->where('auth.pending_reports_count', 1)
                 ->where('auth.pending_approvals_count', 1)
                 ->where('unread_notifications_count', 2)
        );

        // Mark all as read
        $markRes = $this->actingAs($admin)->post(route('notifications.markAllAsRead'), [
            'ids' => ['report-' . $report->id, 'user-' . $pendingUser->id]
        ]);
        $markRes->assertOk();

        // Check after: pending_reports_count, pending_approvals_count, and unread_notifications_count are all 0
        $afterRes = $this->actingAs($admin)->get(route('dashboard'));
        $afterRes->assertOk();
        $afterRes->assertInertia(fn ($page) => 
            $page->where('auth.pending_reports_count', 0)
                 ->where('auth.pending_approvals_count', 0)
                 ->where('unread_notifications_count', 0)
        );
    }
}
