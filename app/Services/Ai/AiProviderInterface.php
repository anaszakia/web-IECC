<?php

namespace App\Services\Ai;

use App\Models\Incident\Incident;

interface AiProviderInterface
{
    /**
     * Klasifikasikan insiden dan ekstrak informasi kritis.
     *
     * Kategori yang mungkin: FIRE, MEDICAL, TRAFFIC, DISASTER, SECURITY, UNKNOWN.
     * UNKNOWN berarti tidak ada kejadian darurat yang jelas atau AI tidak yakin.
     *
     * @param Incident $incident
     * @return array{
     *   category: string,
     *   incident_type: string,
     *   severity: int,                 1-5
     *   victim_estimate: int,
     *   critical_victim: bool,
     *   detected_location: ?string,
     *   required_units: string[],      fire_truck|ambulance|police_patrol|rescue_team|traffic_unit
     *   summary: string,
     *   media_analysis: ?string,
     *   incident_detail: ?string,
     *   risk_analysis: ?string,
     *   first_aid_key: ?string,        burn_wound|bleeding|cpr|choking|recovery_position
     *   confidence: float,             0-1
     *   needs_review: bool,            true jika UNKNOWN, confidence rendah, atau hasil fallback
     *   is_fallback: bool,             true jika klasifikasi TANPA AI (kata kunci)
     *   model_used: ?string,
     *   raw_response: array,
     *   latency_ms: int,
     *   token_input: ?int,
     *   token_output: ?int,
     *   error_message: ?string
     * }
     */
    public function classifyIncident(Incident $incident): array;
}