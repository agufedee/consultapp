<?php

namespace App\Filament\Widgets\Concerns;

use Carbon\Carbon;

/**
 * Shared trait for report widgets that need date range filtering.
 * Eliminates duplication of setDateRange and date properties.
 */
trait HasReportDateRange
{
    public ?Carbon $startDate = null;

    public ?Carbon $endDate = null;

    /**
     * Set the date range for this widget.
     */
    public function setDateRange(Carbon $start, Carbon $end): static
    {
        $this->startDate = $start;
        $this->endDate = $end;

        return $this;
    }

    /**
     * Get the resolved start date (with fallback).
     */
    protected function getResolvedStartDate(Carbon $default): Carbon
    {
        return $this->startDate ?? $default;
    }

    /**
     * Get the resolved end date (with fallback).
     */
    protected function getResolvedEndDate(Carbon $default): Carbon
    {
        return $this->endDate ?? $default;
    }

    /**
     * Get formatted date range description.
     */
    protected function getDateRangeDescription(): string
    {
        return $this->getResolvedStartDate(now())->format('d/m/Y')
            .' al '
            .$this->getResolvedEndDate(now())->format('d/m/Y');
    }
}
