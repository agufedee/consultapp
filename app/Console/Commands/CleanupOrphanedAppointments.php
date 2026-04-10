<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupOrphanedAppointments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-orphaned-appointments {--dry-run : Muestra los registros a eliminar sin ejecutar la eliminación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Encuentra y elimina turnos huérfanos (sin paciente o usuario asociado)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $this->info($dryRun ? '[1;33mMODO SIMULACIÓN (DRY-RUN)[0m' : '[1;32mMODO EJECUCIÓN[0m');

        $this->line('');
        $this->info('Buscando turnos con pacientes inválidos...');

        // Turnos donde patient_id es NULL
        $orphanedByNullPatient = Appointment::whereNull('patient_id');

        // Turnos donde patient_id no corresponde a ningún paciente en la tabla `patients`
        $orphanedByInvalidPatient = Appointment::whereNotNull('patient_id')
            ->whereNotIn('patient_id', DB::table('patients')->pluck('id'));

        $totalPatientOrphans = $orphanedByNullPatient->count() + $orphanedByInvalidPatient->count();

        if ($totalPatientOrphans > 0) {
            $this->warn("Se encontraron {$totalPatientOrphans} turnos huérfanos por paciente.");
            $orphanedByNullPatient->get()->each(fn ($a) => $this->line("  - Turno ID: {$a->id} (patient_id es NULL)"));
            $orphanedByInvalidPatient->get()->each(fn ($a) => $this->line("  - Turno ID: {$a->id} (patient_id {$a->patient_id} no existe)"));
        } else {
            $this->info('✅ No se encontraron turnos huérfanos por paciente.');
        }

        $this->line('');
        $this->info('Buscando turnos con usuarios inválidos...');

        // Turnos donde user_id es NULL
        $orphanedByNullUser = Appointment::whereNull('user_id');

        // Turnos donde user_id no corresponde a ningún usuario en la tabla `users`
        $orphanedByInvalidUser = Appointment::whereNotNull('user_id')
            ->whereNotIn('user_id', DB::table('users')->pluck('id'));

        $totalUserOrphans = $orphanedByNullUser->count() + $orphanedByInvalidUser->count();

        if ($totalUserOrphans > 0) {
            $this->warn("Se encontraron {$totalUserOrphans} turnos huérfanos por usuario.");
            $orphanedByNullUser->get()->each(fn ($a) => $this->line("  - Turno ID: {$a->id} (user_id es NULL)"));
            $orphanedByInvalidUser->get()->each(fn ($a) => $this->line("  - Turno ID: {$a->id} (user_id {$a->user_id} no existe)"));
        } else {
            $this->info('✅ No se encontraron turnos huérfanos por usuario.');
        }

        if ($totalPatientOrphans === 0 && $totalUserOrphans === 0) {
            $this->info("\nLa base de datos está limpia. ¡Buen trabajo!");

            return 0;
        }

        $this->line('');

        if (! $dryRun) {
            if ($this->confirm('¿Desea eliminar permanentemente estos registros?', false)) {
                $deletedPatientCount = $orphanedByNullPatient->delete() + $orphanedByInvalidPatient->delete();
                $deletedUserCount = $orphanedByNullUser->delete() + $orphanedByInvalidUser->delete();
                $this->info("Se eliminaron {$deletedPatientCount} turnos por paciente.");
                $this->info("Se eliminaron {$deletedUserCount} turnos por usuario.");
                $this->info('Limpieza completada.');
            } else {
                $this->info('Operación cancelada.');
            }
        } else {
            $this->comment('Para eliminar los registros, ejecute el comando sin la opción --dry-run.');
        }

        return 0;
    }
}
