<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\Room;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    public function test_report_index_page_is_accessible_by_admin(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::ADMINISTRATOR,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('reports.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => 
            $page->component('ReportExport/Index')
                ->has('reports')
                ->has('stats')
                ->has('rooms')
                ->has('categories')
                ->has('sentiments')
        );
    }

    public function test_pdf_export_generates_download_response(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::ADMINISTRATOR,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('reports.export.pdf'));
        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
    }

    public function test_excel_export_generates_download_response(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::ADMINISTRATOR,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('reports.export.excel'));
        $response->assertOk();
        $this->assertStringContainsString('spreadsheet', $response->headers->get('content-type'));
    }

    public function test_csv_export_generates_download_response(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::ADMINISTRATOR,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('reports.export.csv'));
        $response->assertOk();
        $this->assertStringContainsString('filename=', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('.csv', $response->headers->get('content-disposition'));
    }

    public function test_kasi_with_assigned_room_is_restricted_to_their_room(): void
    {
        $room = Room::first();
        $kasi = User::factory()->create([
            'role_id' => Role::KEPALA_SEKSI,
            'room_id' => $room->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($kasi)->get(route('reports.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => 
            $page->component('ReportExport/Index')
                ->where('isRoomLocked', true)
                ->where('filters.room_id', (string) $room->id)
                ->has('rooms', 1)
        );
    }

    public function test_kasi_without_assigned_room_can_access_all_reports_like_kabid(): void
    {
        $kasi = User::factory()->create([
            'role_id' => Role::KEPALA_SEKSI,
            'room_id' => null,
            'is_active' => true,
        ]);

        $response = $this->actingAs($kasi)->get(route('reports.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => 
            $page->component('ReportExport/Index')
                ->where('isRoomLocked', false)
                ->where('filters.room_id', '')
                ->has('rooms', Room::where('is_active', true)->count())
        );
    }
}
