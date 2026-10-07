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
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->string('code', 20)->unique();
            $table->enum('type', ['AMBULANCE', 'FIRE_TRUCK', 'POLICE_PATROL', 'RESCUE_TEAM', 'TRAFFIC_UNIT', 'OTHER']);
            $table->enum('status', ['AVAILABLE', 'BUSY', 'OFFLINE', 'MAINTENANCE'])->default('OFFLINE');
            $table->boolean('crew_ready')->default(false);
            $table->foreignId('base_facility_id')->nullable()->constrained('facilities')->nullOnDelete();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->geometry('location')->nullable(); // POINT(lng, lat) SRID 0
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index(['agency_id']);
            $table->index(['status', 'type']);
            $table->index(['lat', 'lng']);
        });

        Schema::create('unit_members', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 30)->nullable(); // driver, paramedic, officer
            $table->boolean('on_duty')->default(false);
            $table->timestamps();

            $table->unique(['unit_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_members');
        Schema::dropIfExists('units');
    }
};
