<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewUserRegistered implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $user;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user)
    {
        $this->user = [
            'id' => $user->id,
            'name' => $user->name,
            'nip' => $user->nip,
            'username' => $user->username,
            'unit_name' => $user->room ? $user->room->name : 'Staf RS',
            'created_at' => $user->created_at ? $user->created_at->toIso8601String() : now()->toIso8601String(),
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
        return 'NewUserRegistered';
    }
}
