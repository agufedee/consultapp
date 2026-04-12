<?php

namespace App\Filament\Pages;

use App\Enums\AppointmentStatus;
use App\Filament\Widgets\ReportStatsOverviewWidget;
use App\Models\Appointment;
use App\Services\ReportService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class Reports extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Reportes';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.reports';

    public ?string $filterStartDate = null;

    public ?string $filterEndDate = null;

    public ?string $filterReason = null;

    public ?string $filterStatus = null;

    public function mount(): void
    {
        // Set default dates: current month
        $this->filterStartDate = now()->startOfMonth()->toDateString();
        $this->filterEndDate = now()->endOfMonth()->toDateString();

        $this->form->fill([
            'filterStartDate' => $this->filterStartDate,
            'filterEndDate' => $this->filterEndDate,
            'filterReason' => $this->filterReason,
            'filterStatus' => $this->filterStatus,
        ]);

        $this->updateReportWidgets();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ReportStatsOverviewWidget::class,
        ];
    }

    public function updated($name, $value)
    {
        $this->updateReportWidgets();
    }

    public function updateReportWidgets()
    {
        $this->dispatch('updateReportWidgets',
            $this->filterStartDate,
            $this->filterEndDate,
            $this->filterReason,
            $this->filterStatus
        );
    }

    /**
     * Get the title for this page
     */
    public function getTitle(): string
    {
        return 'Reportes';
    }

    /**
     * Get header actions (export, etc.)
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('clear_filters')
                ->label('Limpiar Filtros')
                ->icon('heroicon-m-x-mark')
                ->action(function () {
                    $this->filterEndDate = now()->endOfMonth()->toDateString();
                    $this->filterStartDate = now()->startOfMonth()->toDateString();
                    $this->filterReason = null;
                    $this->filterStatus = null;
                    Notification::make()
                        ->title('Filtros limpiados')
                        ->success()
                        ->send();
                }),
        ];
    }

    /**
     * Get filter form fields
     */
    protected function getFormSchema(): array
    {
        return [
            DatePicker::make('filterStartDate')
                ->label('Fecha Inicio')
                ->reactive(),

            DatePicker::make('filterEndDate')
                ->label('Fecha Fin')
                ->reactive(),

            Select::make('filterReason')
                ->label('Motivo')
                ->placeholder('Todos')
                ->options($this->getReasonOptions())
                ->searchable()
                ->reactive(),

            Select::make('filterStatus')
                ->label('Estado')
                ->placeholder('Todos')
                ->options(AppointmentStatus::toArray())
                ->reactive(),
        ];
    }

    /**
     * Get unique reasons from appointments
     */
    private function getReasonOptions(): array
    {
        $reasons = Appointment::distinct()
            ->pluck('reason')
            ->filter()
            ->sort()
            ->mapWithKeys(fn ($reason) => [$reason => $reason])
            ->toArray();

        return $reasons;
    }

    /**
     * Get new patients data
     */
    public function getNewPatients(): Collection
    {
        $start = $this->filterStartDate ? Carbon::parse($this->filterStartDate) : now()->startOfMonth();
        $end = $this->filterEndDate ? Carbon::parse($this->filterEndDate) : now()->endOfMonth();

        $patients = app(ReportService::class)->getNewPatients($start, $end);

        // Apply additional filters if needed
        if ($this->filterReason) {
            $patients = $patients->filter(fn ($p) => $p->reason === $this->filterReason);
        }

        return $patients;
    }

    /**
     * Get retention data
     */
    public function getRetention(): Collection
    {
        $start = $this->filterStartDate ? Carbon::parse($this->filterStartDate) : now()->startOfMonth();
        $end = $this->filterEndDate ? Carbon::parse($this->filterEndDate) : now()->endOfMonth();

        $retention = app(ReportService::class)->getPatientRetention($start, $end, 30);

        return $retention;
    }

    /**
     * Get absenteeism data
     */
    public function getAbsenteeism(): Collection
    {
        $start = $this->filterStartDate ? Carbon::parse($this->filterStartDate) : now()->subDays(30);
        $end = $this->filterEndDate ? Carbon::parse($this->filterEndDate) : now();

        $absences = app(ReportService::class)->getAbsenteeismSummary($start, $end);

        // Apply reason filter if needed
        if ($this->filterReason) {
            $absences = $absences->filter(fn ($a) => $a->reason === $this->filterReason);
        }

        return $absences;
    }

    /**
     * Export new patients to CSV
     */
    public function exportNewPatients()
    {
        $data = $this->getNewPatients();

        $filename = 'pacientes-nuevos-'.now()->format('Y-m-d').'.csv';
        $csv = fopen('php://temp', 'r+');

        // Headers
        fputcsv($csv, ['ID Paciente', 'Nombre Paciente', 'Profesional', 'Motivo', 'Fecha Turno', 'Estado']);

        // Data
        foreach ($data as $appointment) {
            // Defensive check for patient and user relationships
            if (! $appointment->patient || ! $appointment->user) {
                continue;
            }
            fputcsv($csv, [
                $appointment->patient_id,
                $appointment->patient->name,
                $appointment->user->name,
                $appointment->reason,
                $appointment->start_date->format('Y-m-d H:i'),
                $appointment->status->value,
            ]);
        }

        rewind($csv);
        $contents = stream_get_contents($csv);
        fclose($csv);

        return response()->streamDownload(
            function () use ($contents) {
                echo $contents;
            },
            $filename,
            ['Content-Type' => 'text/csv']
        );
    }

    /**
     * Export retention data to CSV
     */
    public function exportRetention()
    {
        $data = $this->getRetention();

        $filename = 'retension-'.now()->format('Y-m-d').'.csv';
        $csv = fopen('php://temp', 'r+');

        // Headers
        fputcsv($csv, ['ID Paciente', 'Nombre Paciente', 'Primer Turno', 'Retenido', 'Segundo Turno', 'Días hasta Retención']);

        // Data
        foreach ($data as $record) {
            fputcsv($csv, [
                $record->patient_id,
                $record->patient_name,
                $record->first_appointment_date->format('Y-m-d'),
                $record->retained ? 'Sí' : 'No',
                $record->second_appointment_date?->format('Y-m-d') ?? 'N/A',
                $record->days_to_retention ?? 'N/A',
            ]);
        }

        rewind($csv);
        $contents = stream_get_contents($csv);
        fclose($csv);

        return response()->streamDownload(
            function () use ($contents) {
                echo $contents;
            },
            $filename,
            ['Content-Type' => 'text/csv']
        );
    }

    /**
     * Export absenteeism data to CSV
     */
    public function exportAbsenteeism()
    {
        $data = $this->getAbsenteeism();

        $filename = 'ausentismo-'.now()->format('Y-m-d').'.csv';
        $csv = fopen('php://temp', 'r+');

        // Headers
        fputcsv($csv, ['ID Paciente', 'Nombre Paciente', 'Fecha', 'Hora', 'Día de Semana', 'Franja Horaria', 'Motivo']);

        // Data
        foreach ($data as $record) {
            fputcsv($csv, [
                $record->patient_id,
                $record->patient_name,
                $record->date,
                $record->time,
                $record->day_of_week,
                $record->time_slot,
                $record->reason,
            ]);
        }

        rewind($csv);
        $contents = stream_get_contents($csv);
        fclose($csv);

        return response()->streamDownload(
            function () use ($contents) {
                echo $contents;
            },
            $filename,
            ['Content-Type' => 'text/csv']
        );
    }
}
