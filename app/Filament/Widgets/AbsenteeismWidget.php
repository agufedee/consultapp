<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasReportDateRange;
use App\Services\ReportService;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AbsenteeismWidget extends \Filament\Widgets\StatsOverviewWidget
{
    use HasReportDateRange;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $reportService = app(ReportService::class);
        $absences = $reportService->getAbsenteeismSummary(
            $this->getResolvedStartDate(now()->subDays(30)),
            $this->getResolvedEndDate(now())
        );

        return [
            Stat::make('Ausencias Totales', $absences->count())
                ->description($this->getDateRangeDescription())
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('warning'),
        ];
    }
}
