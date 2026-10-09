<?php

namespace App\Events;

use App\Models\Incident\PatientHandover;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PatientHandoverCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public PatientHandover $handover)
    {
    }

    /**
     * Broadcast ke public channel 'hospital-portal' dan 'command-center'
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('hospital-portal'),
            new Channel('command-center'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'handover.created';
    }

    public function broadcastWith(): array
    {
        $this->handover->loadMissing(['incident', 'assignment.unit', 'assignment.agency', 'facility']);

        return [
            'id'             => $this->handover->id,
            'ulid'           => $this->handover->ulid,
            'incident_id'    => $this->handover->incident_id,
            'facility_id'    => $this->handover->facility_id,
            'facility_name'  => $this->handover->facility?->name,
            'incident'       => [
                'id'          => $this->handover->incident?->id,
                'ulid'        => $this->handover->incident?->ulid,
                'incident_no' => $this->handover->incident?->incident_no,
                'category'    => $this->handover->incident?->category,
                'severity'    => $this->handover->incident?->severity,
            ],
            'unit'           => [
                'code'        => $this->handover->assignment?->unit?->code ?? 'Ambulans',
                'agency_name' => $this->handover->assignment?->agency?->name ?? 'Dinas Kesehatan',
            ],
            'gender'         => $this->handover->gender,
            'age_estimate'   => $this->handover->age_estimate,
            'condition_text' => $this->handover->condition_text,
            'consciousness'  => $this->handover->consciousness,
            'eta_seconds'    => $this->handover->eta_seconds,
            'notified_at'    => $this->handover->notified_at?->toIso8601String() ?? now()->toIso8601String(),
        ];
    }
}
