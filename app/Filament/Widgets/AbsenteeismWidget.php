<?php

namespace App\Filament\Widgets;

use App\Services\ReportService;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AbsenteeismWidget extends BaseWidget
{
    protected ?string $pollingInterval = null;

    public ?Carbon $startDate = null;

    public ?Carbon $endDate = null;

    /**
     * Set the date range for this widget
     */
    public function setDateRange(Carbon $start, Carbon $end): self
    {
        $this->startDate = $start;
        $this->endDate = $end;

        return $this;
    }

    protected function getStats(): array
    {
        $startDate = $this->startDate ?? now()->subDays(30);
        $endDate = $this->endDate ?? now();

        $reportService = app(ReportService::class);
        $absences = $reportService->getAbsenteeismSummary($startDate, $endDate);

        $count = $absences->count();

        return [
            Stat::make('Ausencias Totales', $count)
                ->description($startDate->format('d/m/Y').' al '.$endDate->format('d/m/Y'))
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('warning'),
        ];
    }
}
