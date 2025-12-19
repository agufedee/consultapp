<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Appointment;
use Illuminate\Support\Carbon;

class AttendanceChartWidget extends ChartWidget
{
    protected static bool $isLazy = true;
    protected ?string $heading = 'Asistencia Semanal';

    // Orden 3 para que aparezca después de la tabla y al lado de las métricas
    protected static ?int $sort = 3;

    // Ocupa 1 columna (la mitad de la pantalla abajo)
    protected int | string | array $columnSpan = 'full';

    protected ?string $maxHeight = '250px';

    protected function getData(): array
    {
        $start = now()->startOfWeek();
        $end   = now()->endOfWeek();

        $rows = Appointment::query()
            ->whereHas('status', fn($q) => $q->where('status_name', 'Atendido'))
            ->whereBetween('start_date', [$start, $end])
            ->selectRaw('CAST(start_date AS DATE) as day, COUNT(*) as total')
            ->groupByRaw('CAST(start_date AS DATE)')
            ->orderBy('day')
            ->get();

        // Inicializar lunes a domingo en 0
        $dataByDay = collect(range(0, 6))->map(fn() => 0)->toArray();

        foreach ($rows as $row) {
            $index = Carbon::parse($row->day)->dayOfWeekIso - 1;
            $dataByDay[$index] = $row->total;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pacientes atendidos',
                    'data' => $dataByDay,
                    'backgroundColor' => '#0d9486',
                    'borderRadius' => 4,
                ],
            ],
            'labels' => ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
