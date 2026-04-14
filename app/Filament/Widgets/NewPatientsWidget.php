<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasReportDateRange;
use App\Services\ReportService;
use Filament\Widgets\StatsOverviewWidget\Stat;

class NewPatientsWidget extends \Filament\Widgets\StatsOverviewWidget
{
    use HasReportDateRange;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $reportService = app(ReportService::class);
        $newPatients = $reportService->getNewPatients(
            $this->getResolvedStartDate(now()->startOfMonth()),
            $this->getResolvedEndDate(now()->endOfMonth())
        );

        return [
            Stat::make('Pacientes Nuevos', $newPatients->count())
                ->description($this->getDateRangeDescription())
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
        ];
    }
}
