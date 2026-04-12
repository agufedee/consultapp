<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Converts appointments.status_id (FK to statuses table) to appointments.status (string enum).
     * The statuses table is preserved but no longer referenced by appointments.
     */
    public function up(): void
    {
        // Step 1: Add the new status column
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('status', 50)->nullable()->after('end_date');
        });

        // Step 2: Copy data from status_id to status using the statuses lookup table
        // Map status_name from statuses table to the enum values
        DB::statement("
            UPDATE appointments 
            SET status = (
                SELECT 
                    CASE statuses.status_name
                        WHEN 'scheduled' THEN 'Agendado'
                        WHEN 'confirmed' THEN 'Confirmado'
                        WHEN 'attended' THEN 'Atendido'
                        WHEN 'cancelled' THEN 'Cancelado'
                        WHEN 'absent' THEN 'Ausente'
                        ELSE statuses.status_name
                    END
                FROM statuses 
                WHERE statuses.id = appointments.status_id
            )
            WHERE status_id IS NOT NULL
        ");

        // Step 3: Drop the foreign key constraint first
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['status_id']);
        });

        // Step 4: Drop the old status_id column
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('status_id');
        });

        // Step 5: Make status not nullable
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('status', 50)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add status_id column back
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('status_id')->nullable()->after('end_date');
        });

        // Copy data back from status to status_id (reverse mapping)
        DB::statement("
            UPDATE appointments 
            SET status_id = (
                SELECT statuses.id 
                FROM statuses 
                WHERE 
                    CASE appointments.status
                        WHEN 'Agendado' THEN statuses.status_name = 'scheduled'
                        WHEN 'Confirmado' THEN statuses.status_name = 'confirmed'
                        WHEN 'Atendido' THEN statuses.status_name = 'attended'
                        WHEN 'Cancelado' THEN statuses.status_name = 'cancelled'
                        WHEN 'Ausente' THEN statuses.status_name = 'absent'
                        ELSE FALSE
                    END
                LIMIT 1
            )
            WHERE status IS NOT NULL
        ");

        // Drop the status column
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
