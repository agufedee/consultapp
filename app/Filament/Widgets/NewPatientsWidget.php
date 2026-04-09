<?php

namespace App\Filament\Widgets;

use App\Services\ReportService;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class NewPatientsWidget extends BaseWidget
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
        $startDate = $this->startDate ?? now()->startOfMonth();
        $endDate = $this->endDate ?? now()->endOfMonth();

        $reportService = app(ReportService::class);
        $newPatients = $reportService->getNewPatients($startDate, $endDate);

        $count = $newPatients->count();
        $percentageChange = 0; // Can be calculated from previous month if needed

        return [
            Stat::make('Pacientes Nuevos', $count)
                ->description($startDate->format('d/m/Y').' al '.$endDate->format('d/m/Y'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
        ];
    }
}
