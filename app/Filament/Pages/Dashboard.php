<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Widgets\AttendanceChartWidget;
use App\Filament\Widgets\ReportStatsOverviewWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\TodayAppointmentsWidget;
use CodeWithDennis\FilamentLucideIcons\Enums\LucideIcon;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    // Cambiamos el título
    public function getTitle(): string
    {
        return '¡Hola, '.Auth::user()->name.'!';
    }

    // Agregamos los botones del Header
    protected function getHeaderActions(): array
    {
        return [
            Action::make('agendar')
                ->label('Agendar Nuevo Turno')
                ->icon(LucideIcon::CalendarPlus)
                ->color('primary')
                ->url(AppointmentResource::getUrl('create')), // Te lleva a crear turno

            Action::make('agenda_completa')
                ->label('Ver Agenda Completa')
                ->icon(LucideIcon::Calendar)
                ->color('gray')
                ->url(AppointmentResource::getUrl('index')), // Te lleva al listado
        ];
    }

    public function getWidgets(): array
    {
        return [
            TodayAppointmentsWidget::class,
            StatsOverviewWidget::class,
            AttendanceChartWidget::class,
        ];
    }
}
