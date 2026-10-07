<?php

namespace App\Services\Ai;

use App\Models\Incident\Incident;

interface AiProviderInterface
{
    /**
     * Klasifikasikan insiden dan ekstrak informasi kritis
     *
     * @param Incident $incident
     * @return array [category, incident_type, severity, victim_estimate, critical_victim, required_units, summary, first_aid_key, confidence, raw_response, latency_ms, token_input, token_output]
     */
    public function classifyIncident(Incident $incident): array;
}
