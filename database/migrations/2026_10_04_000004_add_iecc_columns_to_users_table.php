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
        Schema::table('users', function (Blueprint $table) {
            $table->char('ulid', 26)->nullable()->unique()->after('id');
            $table->foreignId('agency_id')->nullable()->after('role_id')->constrained('agencies')->nullOnDelete();
            $table->enum('user_type', ['CITIZEN', 'STAFF', 'FIELD'])->default('STAFF')->after('phone');
            $table->boolean('is_active')->default(true)->after('user_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['agency_id']);
            $table->dropColumn(['ulid', 'agency_id', 'user_type', 'is_active']);
        });
    }
};
