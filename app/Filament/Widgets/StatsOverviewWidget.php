<?php

namespace App\Filament\Widgets;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use CodeWithDennis\FilamentLucideIcons\Enums\LucideIcon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        // 1. Calcular el Total de turnos de este mes
        $totalAppointments = Appointment::query()
            ->whereMonth('start_date', now()->month)
            ->whereYear('start_date', now()->year)
            ->count();

        // 2. Calcular los Ausentes de este mes
        $absentAppointments = Appointment::query()
            ->whereMonth('start_date', now()->month)
            ->whereYear('start_date', now()->year)
            ->where('status', AppointmentStatus::AUSENTE->value)
            ->count();

        // 3. Calcular porcentaje (protegido contra división por cero)
        $absenceRate = $totalAppointments > 0
            ? round(($absentAppointments / $totalAppointments) * 100)
            : 0;

        return [
            Stat::make('Pacientes Activos', Patient::where('active', true)->count())
                ->description('Total registrados')
                ->icon(LucideIcon::Users)
                ->color('success'),

            Stat::make('Turnos este Mes', $totalAppointments)
                ->description('Agendados en '.now()->monthName)
                ->icon(LucideIcon::CalendarDays)
                ->color('info'),

            Stat::make('Tasa de Ausencia', $absenceRate.'%')
                ->description('Promedio mensual')
                ->icon(LucideIcon::TriangleAlert)
                ->color($absenceRate > 15 ? 'danger' : 'success'),
        ];
    }
}
