<?php

namespace App\Jobs;

use App\Models\Incident\Incident;
use App\Models\Incident\IncidentAiAnalysis;
use App\Services\Ai\AiProviderInterface;
use App\Services\Ai\GeminiAiProvider;
use App\Services\Dispatch\DispatchEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeIncidentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 15;

    public function __construct(public Incident $incident)
    {
    }

    public function handle(DispatchEngine $dispatchEngine): void
    {
        Log::info("Memulai AI Analysis & Dispatch Recommendation untuk insiden: {$this->incident->incident_no}");

        /** @var AiProviderInterface $aiProvider */
        $aiProvider = app(GeminiAiProvider::class);

        try {
            // 1. Jalankan Klasifikasi AI
            $analysisResult = $aiProvider->classifyIncident($this->incident);

            // 2. Simpan atau update record incident_ai_analyses
            IncidentAiAnalysis::updateOrCreate(
                ['incident_id' => $this->incident->id],
                [
                    'provider'        => config('ai.provider', 'gemini'),
                    'model'           => config('ai.gemini.model', 'gemini-3.8-flash'),
                    'category'        => $analysisResult['category'],
                    'incident_type'   => $analysisResult['incident_type'],
                    'severity'        => $analysisResult['severity'],
                    'victim_estimate' => $analysisResult['victim_estimate'],
                    'critical_victim' => $analysisResult['critical_victim'],
                    'required_units'  => $analysisResult['required_units'],
                    'summary'         => $analysisResult['summary'],
                    'first_aid_key'   => $analysisResult['first_aid_key'],
                    'confidence'      => $analysisResult['confidence'],
                    'raw_response'    => $analysisResult['raw_response'],
                    'latency_ms'      => $analysisResult['latency_ms'],
                    'token_input'     => $analysisResult['token_input'],
                    'token_output'    => $analysisResult['token_output'],
                    'error_message'   => $analysisResult['error_message'],
                ]
            );

            // 3. Update data insiden dengan hasil AI
            $shouldUpdateCategory = in_array($this->incident->category, ['UNKNOWN', 'AUTO', '', null]);
            $this->incident->update([
                'category'        => $shouldUpdateCategory ? $analysisResult['category'] : $this->incident->category,
                'severity'        => $analysisResult['severity'],
                'severity_source' => 'AI',
                'incident_type'   => $analysisResult['incident_type'],
                'victim_estimate' => $analysisResult['victim_estimate'],
                'ai_status'       => 'DONE',
            ]);

            // 4. Hitung Rekomendasi Dispatch Unit
            $dispatchEngine->generateRecommendations($this->incident);

            Log::info("AI Analysis & Dispatch selesai untuk: {$this->incident->incident_no} (Kategori: {$this->incident->category})");

        } catch (\Throwable $e) {
            Log::error("Gagal menjalankan AnalyzeIncidentJob: {$e->getMessage()}");
            $this->incident->update(['ai_status' => 'FAILED']);
        }
    }
}
