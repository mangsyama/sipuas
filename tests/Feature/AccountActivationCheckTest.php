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

    public function test_unrequested_user_has_has_requested_false_and_is_not_in_approvals(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $admin = User::where('role_id', Role::ADMINISTRATOR)->first();

        // Simulate user JIT imported from Pesu Peluh (belum pilih ruangan, belum minta verifikasi)
        $user = User::create([
            'name' => 'Staf Baru Pesupeluh',
            'username' => 'staf_pesupeluh',
            'nip' => '199501012023011001',
            'email' => 'pesupeluh@example.com',
            'password' => bcrypt('password'),
            'role_id' => Role::STAFF,
            'room_id' => null,
            'phone_number' => '081234567890',
            'is_active' => false,
            'activation_requested_at' => null,
        ]);

        // User visiting activation notice
        $response = $this->actingAs($user)->get(route('activation.notice'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/Activation')
            ->where('user.is_active', false)
            ->where('user.has_requested', false)
        );

        // Admin visiting approvals page should NOT see this user
        $adminResponse = $this->actingAs($admin)->get(route('users.approvals'));
        $adminResponse->assertStatus(200);
        $adminResponse->assertInertia(fn ($page) => $page
            ->component('UserManagement/Approval/Index')
            ->where('stats.pending', 0)
            ->has('users', 0)
        );
    }

    public function test_submitting_activation_request_makes_user_visible_in_approvals(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $admin = User::where('role_id', Role::ADMINISTRATOR)->first();
        $room = Room::first();

        $user = User::create([
            'name' => 'Staf Minta Verifikasi',
            'username' => 'staf_minta_verif',
            'nip' => '199501012023011002',
            'email' => 'minta_verif@example.com',
            'password' => bcrypt('password'),
            'role_id' => Role::STAFF,
            'room_id' => null,
            'phone_number' => '081234567890',
            'is_active' => false,
            'activation_requested_at' => null,
        ]);

        // User submits activation request
        $postRes = $this->actingAs($user)->post(route('activation.request'), [
            'room_id' => $room->id,
            'phone_number' => '081298765432',
        ]);
        $postRes->assertRedirect(route('activation.notice'));

        // Refresh user
        $user->refresh();
        $this->assertNotNull($user->activation_requested_at);
        $this->assertEquals($room->id, $user->room_id);
        $this->assertEquals('081298765432', $user->phone_number);

        // User activation notice now has has_requested = true
        $userNotice = $this->actingAs($user)->get(route('activation.notice'));
        $userNotice->assertStatus(200);
        $userNotice->assertInertia(fn ($page) => $page
            ->component('Auth/Activation')
            ->where('user.has_requested', true)
        );

        // Admin now sees this user in approvals
        $adminResponse = $this->actingAs($admin)->get(route('users.approvals'));
        $adminResponse->assertStatus(200);
        $adminResponse->assertInertia(fn ($page) => $page
            ->component('UserManagement/Approval/Index')
            ->where('stats.pending', 1)
            ->has('users', 1)
        );
    }

    public function test_deleted_user_restored_on_relogin_resets_activation_state(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $room = Room::first();

        // Previously active user
        $user = User::create([
            'name' => 'Staf Dihapus',
            'username' => 'staf_dihapus',
            'nip' => '199501012023011003',
            'email' => 'dihapus@example.com',
            'password' => bcrypt('password123'),
            'role_id' => Role::STAFF,
            'room_id' => $room->id,
            'phone_number' => '081234567890',
            'is_active' => true,
            'approved_at' => now(),
            'activation_requested_at' => now(),
        ]);

        // Admin deletes the user (soft delete)
        $user->delete();
        $this->assertTrue($user->trashed());

        // User attempts login again
        $loginRes = $this->post(route('login'), [
            'username' => 'staf_dihapus',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
        $loginRes->assertRedirect(route('activation.notice'));

        // Check that user is restored, but reset to inactive and unrequested
        $user->refresh();
        $this->assertFalse($user->trashed());
        $this->assertFalse((bool) $user->is_active);
        $this->assertNull($user->room_id);
        $this->assertNull($user->approved_at);
        $this->assertNull($user->approved_by);
        $this->assertNull($user->activation_requested_at);

        // Verify that notice page shows has_requested = false
        $noticeRes = $this->actingAs($user)->get(route('activation.notice'));
        $noticeRes->assertStatus(200);
        $noticeRes->assertInertia(fn ($page) => $page
            ->component('Auth/Activation')
            ->where('user.is_active', false)
            ->where('user.has_requested', false)
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

    public function test_admin_can_reject_user_registration_without_foreign_key_violation(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $admin = User::where('role_id', Role::ADMINISTRATOR)->first();
        $room = Room::first();

        $user = User::create([
            'name' => 'Staf Akan Ditolak',
            'username' => 'staf_ditolak',
            'nip' => '199501012023011099',
            'email' => 'ditolak@example.com',
            'password' => bcrypt('password123'),
            'role_id' => Role::STAFF,
            'room_id' => $room->id,
            'phone_number' => '081234567890',
            'is_active' => false,
            'activation_requested_at' => now(),
        ]);

        // Attach a foreign key relation to simulate historical activity (e.g. verified_by on Report)
        \App\Models\Report::create([
            'ticket_number' => 'LP-REJECT-FK-TEST',
            'room_id' => $room->id,
            'isi_laporan' => 'Test laporan riwayat staf',
            'status' => 'SOLVED',
            'verified_by' => $user->id,
        ]);

        // Admin rejects the registration
        $rejectRes = $this->actingAs($admin)->delete(route('users.approvals.reject', $user->id));
        $rejectRes->assertRedirect(route('users.approvals'));
        $rejectRes->assertSessionHas('success');

        // Check user is soft deleted, not crashing on foreign key
        $this->assertTrue($user->fresh()->trashed());

        // Check user is no longer in pending approvals
        $adminResponse = $this->actingAs($admin)->get(route('users.approvals'));
        $adminResponse->assertInertia(fn ($page) => $page
            ->where('stats.pending', 0)
            ->has('users', 0)
        );
    }

    public function test_pesupeluh_photo_sync_on_activation(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        // Prepare dummy photo in storage
        $testPhotoName = 'profile_test_sync_123.jpg';
        $testPhotoDir = storage_path('app/public/profile_photos');
        if (!is_dir($testPhotoDir)) {
            @mkdir($testPhotoDir, 0755, true);
        }
        file_put_contents($testPhotoDir . DIRECTORY_SEPARATOR . $testPhotoName, 'fake-image-binary');

        $user = User::create([
            'name' => 'Staf Sync Foto',
            'username' => 'staf_sync_foto',
            'nip' => '199501012023011099',
            'email' => 'syncfoto@example.com',
            'password' => bcrypt('password'),
            'role_id' => Role::STAFF,
            'room_id' => null,
            'phone_number' => '081234567890',
            'profile_photo_path' => null,
            'is_active' => false,
            'activation_requested_at' => null,
        ]);

        $synced = \App\Services\PesupeluhService::syncUserProfilePhoto($user, '/storage/profile_photos/' . $testPhotoName);
        $this->assertEquals('/storage/profile_photos/' . $testPhotoName, $synced);
        $this->assertEquals('/storage/profile_photos/' . $testPhotoName, $user->fresh()->profile_photo_path);

        // Visiting activation notice should carry the synced photo
        $response = $this->actingAs($user)->get(route('activation.notice'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('user.profile_photo_path', '/storage/profile_photos/' . $testPhotoName)
        );

        // Clean up
        @unlink($testPhotoDir . DIRECTORY_SEPARATOR . $testPhotoName);
    }

    public function test_approve_requires_role_selection(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $admin = User::where('role_id', Role::ADMINISTRATOR)->first();

        $user = User::create([
            'name' => 'Staf Perlu Peran',
            'username' => 'staf_perlu_peran',
            'nip' => '199501012023011088',
            'email' => 'perlu_peran@example.com',
            'password' => bcrypt('password'),
            'role_id' => Role::STAFF,
            'is_active' => false,
            'activation_requested_at' => now(),
        ]);

        // Attempt approve without role or role_id
        $response = $this->actingAs($admin)->post(route('users.approvals.approve', $user->id), [
            'role' => '',
            'role_id' => '',
        ]);

        $response->assertSessionHasErrors(['role', 'role_id']);
        $this->assertFalse($user->fresh()->is_active);
    }

    public function test_pending_approval_users_do_not_appear_in_user_management_index(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $admin = User::where('role_id', Role::ADMINISTRATOR)->first();
        $room = Room::first();

        // 1. Unapproved applicant waiting in approval queue
        $pendingUser = User::create([
            'name' => 'Pemohon Verifikasi',
            'username' => 'pemohon_verif',
            'nip' => '199501012023011077',
            'email' => 'pemohon@example.com',
            'password' => bcrypt('password'),
            'role_id' => Role::STAFF,
            'room_id' => $room->id,
            'phone_number' => '081234567877',
            'is_active' => false,
            'activation_requested_at' => now(),
            'approved_at' => null,
        ]);

        // 2. JIT imported user who hasn't submitted activation yet
        $jitUser = User::create([
            'name' => 'JIT Belum Minta',
            'username' => 'jit_belum_minta',
            'nip' => '199501012023011078',
            'email' => 'jit@example.com',
            'password' => bcrypt('password'),
            'role_id' => Role::STAFF,
            'room_id' => null,
            'is_active' => false,
            'activation_requested_at' => null,
            'approved_at' => null,
        ]);

        // Admin visits User Management (Daftar Pengguna)
        $res = $this->actingAs($admin)->get(route('users.index'));
        $res->assertStatus(200);
        $res->assertInertia(fn ($page) => $page
            ->component('UserManagement/Index')
            ->where('stats.total', 1) // Only admin
            ->where('stats.active', 1)
            ->where('stats.pending', 1) // 1 pending approval
            ->has('users', 1) // Only admin in the list
            ->where('users.0.username', 'admin')
        );

        // Admin approves the pending user
        $approveRes = $this->actingAs($admin)->post(route('users.approvals.approve', $pendingUser->id), [
            'role' => 'STAFF',
            'role_id' => Role::STAFF,
            'room_id' => $room->id,
        ]);
        $approveRes->assertRedirect(route('users.approvals'));

        // After approval, user NOW appears in User Management
        $resAfterApprove = $this->actingAs($admin)->get(route('users.index'));
        $resAfterApprove->assertStatus(200);
        $resAfterApprove->assertInertia(fn ($page) => $page
            ->where('stats.total', 2)
            ->where('stats.active', 2)
            ->where('stats.pending', 0)
            ->has('users', 2)
        );

        // If admin toggles this approved user to inactive (Nonaktif)
        $toggleRes = $this->actingAs($admin)->patch(route('users.toggle-status', $pendingUser->id));
        $toggleRes->assertRedirect();

        // The deactivated approved user STILL appears in User Management as inactive
        $resAfterToggle = $this->actingAs($admin)->get(route('users.index'));
        $resAfterToggle->assertStatus(200);
        $resAfterToggle->assertInertia(fn ($page) => $page
            ->where('stats.total', 2)
            ->where('stats.active', 1)
            ->where('stats.pending', 0)
            ->has('users', 2)
        );
    }
}
