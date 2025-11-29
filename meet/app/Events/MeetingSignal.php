<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MeetingSignal implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $meetingSlug,
        public array $data,
        public ?string $targetSessionId = null
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('meeting.' . $this->meetingSlug);
    }

    public function broadcastAs(): string
    {
        return 'signal';
    }
}
