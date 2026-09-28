<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimezoneRelativeDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_created_at_normalizes_sql_server_utc_offset_to_wita(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-28 10:40:00', 'Asia/Makassar'));

        $report = new Report();
        // Emulate SQL Server DATETIMEOFFSET return value
        $report->setRawAttributes([
            'ticket_number' => 'LP-TEST-SQLSRV',
            'created_at' => '2026-09-28 10:39:00.0000000 +00:00',
        ]);

        $this->assertEquals('Asia/Makassar', $report->created_at->getTimezone()->getName());
        $this->assertEquals('28 Sep 2026, 10:39', $report->created_at->format('d M Y, H:i'));
        $this->assertStringNotContainsString('dari sekarang', $report->created_at_human);
        $this->assertStringNotContainsString('setelahnya', $report->created_at_human);
        $this->assertStringContainsString('yang lalu', $report->created_at_human);

        Carbon::setTestNow();
    }

    public function test_report_created_at_handles_sqlite_plain_datetime(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-28 10:40:00', 'Asia/Makassar'));

        $report = new Report();
        // Emulate SQLite / MySQL plain datetime
        $report->setRawAttributes([
            'ticket_number' => 'LP-TEST-SQLITE',
            'created_at' => '2026-09-28 10:39:00',
        ]);

        $this->assertEquals('Asia/Makassar', $report->created_at->getTimezone()->getName());
        $this->assertEquals('28 Sep 2026, 10:39', $report->created_at->format('d M Y, H:i'));
        $this->assertStringNotContainsString('dari sekarang', $report->created_at_human);
        $this->assertStringContainsString('yang lalu', $report->created_at_human);

        Carbon::setTestNow();
    }

    public function test_created_at_human_returns_baru_saja_if_timestamp_is_in_the_future(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-28 10:40:00', 'Asia/Makassar'));

        $report = new Report();
        $report->setRawAttributes([
            'ticket_number' => 'LP-TEST-FUTURE',
            'created_at' => '2026-09-28 10:40:05', // 5 seconds ahead due to slight clock sync difference
        ]);

        $this->assertEquals('Baru saja', $report->created_at_human);

        Carbon::setTestNow();
    }
}
