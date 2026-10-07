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
        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->foreignId('agency_id')->nullable()->constrained('agencies')->nullOnDelete();
            $table->enum('type', ['HOSPITAL', 'PUSKESMAS', 'FIRE_STATION', 'POLICE_STATION', 'SHELTER']);
            $table->string('name', 150);
            $table->string('address', 255)->nullable();
            $table->string('phone', 30)->nullable();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->geometry('location')->nullable(); // POINT(lng, lat) SRID 0
            $table->json('services')->nullable();     // ["trauma","obstetric","neonatal","icu"]
            $table->unsignedSmallInteger('er_beds_total')->nullable();
            $table->unsignedSmallInteger('er_beds_available')->nullable();
            $table->enum('er_status', ['NORMAL', 'BUSY', 'FULL'])->default('NORMAL');
            $table->timestamp('er_updated_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('type');
            $table->index(['lat', 'lng']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facilities');
    }
};
