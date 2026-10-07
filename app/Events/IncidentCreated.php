<?php

namespace App\Events;

use App\Models\Incident\Incident;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncidentCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Incident $incident)
    {
    }

    /**
     * Broadcast ke public channel command-center agar operator web langsung mendapat update
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('command-center'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'incident.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id'                  => $this->incident->id,
            'ulid'                => $this->incident->ulid,
            'incident_no'         => $this->incident->incident_no,
            'category'            => $this->incident->category,
            'severity'            => $this->incident->severity,
            'status'              => $this->incident->status,
            'description'         => $this->incident->description,
            'address_text'        => $this->incident->address_text,
            'lat'                 => (float) $this->incident->lat,
            'lng'                 => (float) $this->incident->lng,
            'reported_at'         => $this->incident->reported_at?->format('Y-m-d H:i:s'),
            'media'               => $this->incident->media->map(fn($m) => [
                'type' => $m->type,
                'path' => $m->disk_path,
            ]),
        ];
    }
}
