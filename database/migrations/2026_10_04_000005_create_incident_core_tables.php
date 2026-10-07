<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Token Perangkat (FCM)
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('fcm_token', 255)->unique();
            $table->enum('platform', ['android', 'ios'])->default('android');
            $table->enum('app', ['citizen', 'field']);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });

        // Insiden (One Incident ID)
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->string('incident_no', 30)->unique(); // INC-YYYY-MM-DD-NNNNNN
            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('source', ['APP', 'CALL_CENTER', 'SILENT', 'OPERATOR', 'SMS'])->default('APP');
            $table->enum('category', ['MEDICAL', 'FIRE', 'DISASTER', 'SECURITY', 'TRAFFIC', 'UNKNOWN'])->default('UNKNOWN');
            $table->string('incident_type', 60)->nullable();
            $table->unsignedTinyInteger('severity')->nullable(); // 1-4
            $table->enum('severity_source', ['AI', 'OPERATOR', 'CITIZEN'])->nullable();
            $table->enum('status', [
                'NEW', 'VERIFIED', 'DISPATCHED', 'ACCEPTED', 'EN_ROUTE', 'ARRIVED',
                'HANDLING', 'TRANSFERRED', 'RESOLVED', 'CLOSED',
                'CANCELLED', 'DUPLICATE', 'FALSE_REPORT'
            ])->default('NEW');
            $table->text('description')->nullable();
            $table->string('address_text', 255)->nullable();
            $table->string('district', 100)->nullable();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->geometry('location')->nullable(); // POINT(lng, lat) SRID 0
            $table->unsignedSmallInteger('location_accuracy_m')->nullable();
            $table->unsignedSmallInteger('victim_estimate')->nullable();
            $table->foreignId('duplicate_of_id')->nullable()->constrained('incidents')->nullOnDelete();
            $table->enum('ai_status', ['PENDING', 'DONE', 'FAILED', 'SKIPPED'])->default('PENDING');
            $table->timestamp('reported_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('first_accepted_at')->nullable();
            $table->timestamp('first_arrived_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['category', 'severity']);
            $table->index('reported_at');
            $table->index(['lat', 'lng']);
        });

        // Media Laporan
        Schema::create('incident_media', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->foreignId('incident_id')->constrained('incidents')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('type', ['PHOTO', 'VIDEO', 'AUDIO']);
            $table->string('disk_path', 255);
            $table->string('mime', 80)->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('transcript')->nullable(); // hasil STT bila AUDIO
            $table->timestamps();

            $table->index('incident_id');
        });

        // Hasil Analisis AI
        Schema::create('incident_ai_analyses', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->foreignId('incident_id')->unique()->constrained('incidents')->cascadeOnDelete();
            $table->string('provider', 30); // gemini
            $table->string('model', 60);
            $table->string('category', 20)->nullable();
            $table->string('incident_type', 60)->nullable();
            $table->unsignedTinyInteger('severity')->nullable();
            $table->unsignedSmallInteger('victim_estimate')->nullable();
            $table->boolean('critical_victim')->nullable();
            $table->json('required_units')->nullable(); // ["ambulance","police"]
            $table->string('summary', 500)->nullable();
            $table->string('first_aid_key', 50)->nullable();
            $table->decimal('confidence', 4, 3)->nullable();
            $table->json('raw_response')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->unsignedInteger('token_input')->nullable();
            $table->unsignedInteger('token_output')->nullable();
            $table->string('error_message', 255)->nullable();
            $table->foreignId('overridden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Penugasan Unit ke Insiden
        Schema::create('incident_assignments', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->foreignId('incident_id')->constrained('incidents')->cascadeOnDelete();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', [
                'DISPATCHED', 'ACCEPTED', 'REJECTED', 'EN_ROUTE', 'ARRIVED',
                'HANDLING', 'TRANSFERRED', 'RESOLVED', 'CANCELLED'
            ])->default('DISPATCHED');
            $table->string('reject_reason', 255)->nullable();
            $table->unsignedInteger('eta_seconds')->nullable();
            $table->unsignedInteger('distance_m')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('en_route_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('incident_id');
            $table->index('agency_id');
            $table->index(['unit_id', 'status']);
        });

        // Log Status (Audit Timeline)
        Schema::create('incident_status_logs', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->foreignId('incident_id')->constrained('incidents')->cascadeOnDelete();
            $table->foreignId('assignment_id')->nullable()->constrained('incident_assignments')->nullOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('actor_type', ['USER', 'SYSTEM', 'AI'])->default('USER');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamp('occurred_at', 3)->useCurrent();
            $table->timestamp('synced_at', 3)->nullable();
            $table->timestamps();

            $table->index(['incident_id', 'occurred_at']);
        });

        // Rekomendasi Dispatch
        Schema::create('dispatch_recommendations', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->foreignId('incident_id')->constrained('incidents')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->unsignedTinyInteger('rank_no');
            $table->decimal('score', 6, 2);
            $table->unsignedInteger('distance_m')->nullable();
            $table->unsignedInteger('eta_seconds')->nullable();
            $table->foreignId('destination_facility_id')->nullable()->constrained('facilities')->nullOnDelete();
            $table->json('score_breakdown')->nullable();
            $table->boolean('chosen')->default(false);
            $table->timestamps();

            $table->index(['incident_id', 'rank_no']);
        });

        // Data Pasien Singkat
        Schema::create('patient_handovers', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->foreignId('incident_id')->constrained('incidents')->cascadeOnDelete();
            $table->foreignId('assignment_id')->constrained('incident_assignments')->cascadeOnDelete();
            $table->foreignId('facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->enum('gender', ['M', 'F', 'UNKNOWN'])->default('UNKNOWN');
            $table->unsignedTinyInteger('age_estimate')->nullable();
            $table->text('condition_text')->nullable(); // Terenkripsi
            $table->enum('consciousness', ['ALERT', 'VERBAL', 'PAIN', 'UNRESPONSIVE'])->nullable();
            $table->json('requested_services')->nullable(); // ["emergency_room","trauma_team"]
            $table->unsignedInteger('eta_seconds')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['facility_id', 'received_at']);
        });

        // Snapshot Posisi Unit
        Schema::create('unit_location_snapshots', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('incident_id')->nullable()->constrained('incidents')->nullOnDelete();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->decimal('speed_kmh', 5, 1)->nullable();
            $table->smallInteger('heading')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['unit_id', 'recorded_at']);
        });

        // Audit Log
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 60); // VIEW_PATIENT, EXPORT, DISPATCH, dll.
            $table->string('entity_type', 60)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('unit_location_snapshots');
        Schema::dropIfExists('patient_handovers');
        Schema::dropIfExists('dispatch_recommendations');
        Schema::dropIfExists('incident_status_logs');
        Schema::dropIfExists('incident_assignments');
        Schema::dropIfExists('incident_ai_analyses');
        Schema::dropIfExists('incident_media');
        Schema::dropIfExists('incidents');
        Schema::dropIfExists('device_tokens');
    }
};
