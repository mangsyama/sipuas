<?php

namespace Tests\Feature;

use App\Imports\StaffImport;
use App\Models\Role;
use App\Models\Room;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class StaffManagementImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    public function test_import_with_only_name_and_status_succeeds(): void
    {
        $import = new StaffImport();

        $rows = new Collection([
            [
                'nama_staf' => 'Ni Wayan Sukerti',
                'status_aktif' => 'Aktif',
                'nip' => '',
                'nomor_hp' => '',
                'jabatan' => '',
                'ruangan' => '',
            ],
            [
                'nama_staf' => 'I Ketut Suartana',
                'status_aktif' => 'Tidak Aktif',
                'nip' => null,
                'nomor_hp' => null,
                'jabatan' => null,
                'ruangan' => null,
            ],
        ]);

        $import->collection($rows);

        $this->assertEquals(2, $import->createdCount);
        $this->assertEquals(0, $import->skippedCount);
        $this->assertEmpty($import->errors);

        $staff1 = Staff::where('name', 'Ni Wayan Sukerti')->first();
        $this->assertNotNull($staff1);
        $this->assertTrue($staff1->is_active);
        $this->assertNull($staff1->nip);
        $this->assertNull($staff1->phone_number);
        $this->assertEquals('Staf Pelaksana', $staff1->position);

        $staff2 = Staff::where('name', 'I Ketut Suartana')->first();
        $this->assertNotNull($staff2);
        $this->assertFalse($staff2->is_active);
        $this->assertNull($staff2->nip);
        $this->assertNull($staff2->phone_number);
        $this->assertEquals('Staf Pelaksana', $staff2->position);
    }

    public function test_import_skips_when_name_is_empty(): void
    {
        $import = new StaffImport();

        $rows = new Collection([
            [
                'nama_staf' => '',
                'status_aktif' => 'Aktif',
                'nip' => '199001012020011001',
            ],
        ]);

        $import->collection($rows);

        $this->assertEquals(0, $import->createdCount);
        $this->assertEquals(1, $import->skippedCount);
        $this->assertCount(1, $import->errors);
        $this->assertStringContainsString('Nama staf wajib diisi', $import->errors[0]);
    }

    public function test_import_skips_when_status_is_empty(): void
    {
        $import = new StaffImport();

        $rows = new Collection([
            [
                'nama_staf' => 'Made Wirawan',
                'status_aktif' => '',
            ],
        ]);

        $import->collection($rows);

        $this->assertEquals(0, $import->createdCount);
        $this->assertEquals(1, $import->skippedCount);
        $this->assertCount(1, $import->errors);
        $this->assertStringContainsString('Status aktif wajib diisi', $import->errors[0]);
    }

    public function test_import_updates_existing_staff_by_name_when_nip_is_empty(): void
    {
        $existing = Staff::create([
            'name' => 'Gede Dharma',
            'position' => 'Perawat',
            'is_active' => true,
        ]);

        $import = new StaffImport();

        $rows = new Collection([
            [
                'nama' => 'Gede Dharma',
                'status' => 'Tidak Aktif',
                'nip' => '',
            ],
        ]);

        $import->collection($rows);

        $this->assertEquals(0, $import->createdCount);
        $this->assertEquals(1, $import->updatedCount);

        $existing->refresh();
        $this->assertFalse($existing->is_active);
        $this->assertEquals('Perawat', $existing->position); // Position kept
    }

    public function test_import_endpoint_as_admin(): void
    {
        $admin = User::where('role_id', Role::ADMINISTRATOR)->first();

        // Create a fake CSV file
        $csvContent = "Nama Staf,Status Aktif,NIP,Nomor HP,Jabatan,Ruangan\n"
                    . "Komang Aryasa,Aktif,,,,\n"
                    . "Putu Dewi,Tidak Aktif,,,,\n";

        $file = UploadedFile::fake()->createWithContent('staf_test.csv', $csvContent);

        $response = $this->actingAs($admin)->post(route('staff-management.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('staff', [
            'name' => 'Komang Aryasa',
            'is_active' => true,
            'position' => 'Staf Pelaksana',
        ]);

        $this->assertDatabaseHas('staff', [
            'name' => 'Putu Dewi',
            'is_active' => false,
            'position' => 'Staf Pelaksana',
        ]);
    }
}
