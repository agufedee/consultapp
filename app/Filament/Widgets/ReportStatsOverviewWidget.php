<?php

namespace App\Filament\Widgets;

use App\Services\ReportService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;

class ReportStatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    public ?string $filterStartDate = null;

    public ?string $filterEndDate = null;

    public ?string $filterReason = null;

    public ?string $filterStatus = null;

    #[On('updateReportWidgets')]
    public function updateFilters(?string $startDate, ?string $endDate, ?string $reason, ?string $status): void
    {
        $this->filterStartDate = $startDate;
        $this->filterEndDate = $endDate;
        $this->filterReason = $reason;
        $this->filterStatus = $status;
    }

    protected function getStats(): array
    {
        $reportService = app(ReportService::class);

        // Parse dates safely
        $start = $this->filterStartDate ? Carbon::parse($this->filterStartDate) : now()->startOfMonth();
        $end = $this->filterEndDate ? Carbon::parse($this->filterEndDate) : now()->endOfMonth();

        // 1. Get New Patients Count
        $newPatients = $reportService->getNewPatients($start, $end);
        if ($this->filterReason) {
            $newPatients = $newPatients->where('reason', $this->filterReason);
        }
        if ($this->filterStatus) {
            $newPatients = $newPatients->where('status', $this->filterStatus);
        }
        $newPatientsCount = $newPatients->count();

        // 2. Get Retention Rate
        $retentionData = $reportService->getPatientRetention($start, $end, 30);
        $retainedCount = $retentionData->where('retained', true)->count();
        $totalInCohort = $retentionData->count();
        $retentionRate = $totalInCohort > 0 ? round(($retainedCount / $totalInCohort) * 100) : 0;

        // 3. Get Absenteeism Count
        $absences = $reportService->getAbsenteeismSummary($start, $end);
        if ($this->filterReason) {
            $absences = $absences->where('reason', 'like', '%'.$this->filterReason.'%');
        }
        $absenteeismCount = $absences->count();

        return [
            Stat::make('Pacientes Nuevos', $newPatientsCount)
                ->description('En el período seleccionado')
                ->color('success'),

            Stat::make('Tasa de Retención (30 días)', $retentionRate.'%')
                ->description('Pacientes que volvieron')
                ->color($retentionRate >= 50 ? 'success' : 'warning'),

            Stat::make('Ausencias Totales', $absenteeismCount)
                ->description('En el período seleccionado')
                ->color($absenteeismCount > 0 ? 'danger' : 'success'),
        ];
    }
}
