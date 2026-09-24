<?php

namespace App\Events;

use App\Models\Report;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewReportSubmitted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $report;

    /**
     * Create a new event instance.
     */
    public function __construct(Report $report)
    {
        $this->report = [
            'id' => $report->id,
            'ticket_number' => $report->ticket_number,
            'room_id' => $report->room_id,
            'room_name' => $report->room ? $report->room->name : 'Unit Pelayanan',
            'isi_laporan' => \Illuminate\Support\Str::limit($report->isi_laporan, 100),
            'priority' => $report->priority ?? 'NORMAL',
            'created_at' => $report->created_at ? $report->created_at->toIso8601String() : now()->toIso8601String(),
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('sipuas-notifications'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'NewReportSubmitted';
    }
}
