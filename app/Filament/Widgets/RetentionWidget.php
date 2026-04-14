<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasReportDateRange;
use App\Services\ReportService;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RetentionWidget extends \Filament\Widgets\StatsOverviewWidget
{
    use HasReportDateRange;

    protected ?string $pollingInterval = null;

    public int $retentionDays = 30;

    protected function getStats(): array
    {
        $reportService = app(ReportService::class);
        $retention = $reportService->getPatientRetention(
            $this->getResolvedStartDate(now()->startOfMonth()),
            $this->getResolvedEndDate(now()->endOfMonth()),
            $this->retentionDays
        );

        $total = $retention->count();
        $retained = $retention->where('retained', true)->count();
        $percentage = $total > 0 ? round(($retained / $total) * 100, 2) : 0;

        return [
            Stat::make("Retención {$this->retentionDays}d", $percentage.'%')
                ->description("{$retained} de {$total} pacientes retenidos")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }
}
