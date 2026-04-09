<?php

namespace App\Filament\Widgets;

use App\Services\ReportService;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RetentionWidget extends BaseWidget
{
    protected ?string $pollingInterval = null;

    public ?Carbon $startDate = null;

    public ?Carbon $endDate = null;

    public int $retentionDays = 30;

    /**
     * Set the date range and retention window for this widget
     */
    public function setDateRange(Carbon $start, Carbon $end, int $days = 30): self
    {
        $this->startDate = $start;
        $this->endDate = $end;
        $this->retentionDays = $days;

        return $this;
    }

    protected function getStats(): array
    {
        $startDate = $this->startDate ?? now()->startOfMonth();
        $endDate = $this->endDate ?? now()->endOfMonth();

        $reportService = app(ReportService::class);
        $retention = $reportService->getPatientRetention($startDate, $endDate, $this->retentionDays);

        $total = $retention->count();
        $retained = $retention->where('retained', true)->count();
        $percentage = $total > 0 ? round(($retained / $total) * 100, 2) : 0;

        return [
            Stat::make('Retención '.$this->retentionDays.'d', $percentage.'%')
                ->description($retained.' de '.$total.' pacientes retenidos')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }
}
