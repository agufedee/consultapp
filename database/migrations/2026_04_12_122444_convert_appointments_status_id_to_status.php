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

        // Copy data back from status to status_id using multiple UPDATE statements
        // (CASE in WHERE clause is not valid MySQL syntax)
        $statusMappings = [
            'Agendado' => 'scheduled',
            'Confirmado' => 'confirmed',
            'Atendido' => 'attended',
            'Cancelado' => 'cancelled',
            'Ausente' => 'absent',
        ];

        foreach ($statusMappings as $spanishStatus => $englishStatus) {
            DB::statement("
                UPDATE appointments
                SET status_id = (SELECT id FROM statuses WHERE status_name = ? LIMIT 1)
                WHERE status = ?
            ", [$englishStatus, $spanishStatus]);
        }

        // Drop the status column
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
