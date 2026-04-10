<?php

namespace App\Filament\Resources\Appointments\Widgets;

use App\Enums\AppointmentStatus;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Appointment;
use Filament\Actions\Action;
// Acciones del plugin
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class AppointmentCalendarWidget extends FullCalendarWidget
{
    protected static bool $isLazy = true;

    public Model|string|null $model = Appointment::class;

    /**
     * 1. Cargar Eventos de la Base de Datos
     */
    public function fetchEvents(array $fetchInfo): array
    {
        $start = Carbon::parse($fetchInfo['start'])->startOfDay();
        $end = Carbon::parse($fetchInfo['end'])->endOfDay();

        return Appointment::query()
            ->with(['patient'])
            ->whereBetween('start_date', [$start, $end])
            ->get()
            ->map(fn (Appointment $event) => [
                'id' => $event->id,
                'title' => $event->patient?->full_name ?? 'Sin Paciente',
                'start' => $event->start_date,
                'end' => $event->end_date,
                'color' => match ($event->status instanceof AppointmentStatus ? $event->status->value : (string) $event->status) {
                    AppointmentStatus::AGENDADO->value => '#6b7280',
                    AppointmentStatus::CONFIRMADO->value => '#3b82f6',
                    AppointmentStatus::ATENDIDO->value => '#22c55e',
                    AppointmentStatus::CANCELADO->value => '#ef4444',
                    AppointmentStatus::AUSENTE->value => '#f59e0b',
                    default => '#3b82f6',
                },
            ])
            ->toArray();
    }

    /**
     * 2. Definir el Formulario del Modal (Overlay)
     * ¡Reutilizamos el del Recurso para no escribirlo dos veces!
     */
    public function getSchema(string $name): ?Schema
    {
        // Obtenemos la estructura base y le inyectamos tu formulario
        $schema = parent::getSchema($name);

        if ($schema) {
            return AppointmentResource::form($schema)
                ->model($this->getModel()); // Importante para rellenar datos al editar
        }

        return null;
    }

    public function onEventClick(array $info): void
    {
        $id = $info['event']['id'] ?? $info['id'] ?? null;

        if ($id) {
            $id = $info['event']['id'] ?? $info['id'] ?? null;

            if ($id) {
                // Pasamos el ID en un array de argumentos con nombre 'record_id'
                $this->mountAction('edit', ['record_id' => $id]);
            }
        }
    }

    /**
     * 3. Configurar Acciones (Botones)
     */
    protected function headerActions(): array
    {
        return [// Ancho grande para tu diseño de 3 columnas
        ];
    }

    protected function modalActions(): array
    {
        return [
            // 1. ACCIÓN DE EDITAR (MANUAL Y ROBUSTA)
            Action::make('edit')
                ->label('Guardar Cambios')
                ->modalHeading('Editar Turno')
                ->modalWidth('5xl')
                // Conectamos tu formulario de 2 columnas
                ->form(fn (Schema $schema) => AppointmentResource::form($schema))

                // A. Cargar datos al abrir el modal
                ->mountUsing(function ($form, array $arguments) {
                    // Buscamos el turno usando el ID que pasamos en onEventClick
                    $appointment = Appointment::find($arguments['record_id']);

                    if ($appointment) {
                        $form->fill($appointment->toArray());
                    }
                })

                // B. Guardar datos al confirmar
                ->action(function (array $data, array $arguments) {
                    $appointment = Appointment::find($arguments['record_id']);

                    if ($appointment) {
                        $appointment->update($data);

                        \Filament\Notifications\Notification::make()
                            ->title('Turno actualizado')
                            ->success()
                            ->send();
                    }
                }),

            // 2. ACCIÓN DE ELIMINAR (MANUAL PARA EVITAR ERRORES TAMBIÉN)
            Action::make('delete')
                ->label('Eliminar')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Eliminar Turno')
                ->action(function (array $arguments) {
                    $appointment = Appointment::find($arguments['record_id']);
                    if ($appointment) {
                        $appointment->delete();

                        \Filament\Notifications\Notification::make()
                            ->title('Turno eliminado')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    /**
     * 4. Configuración Visual del Calendario
     */
    public function config(): array
    {
        return [
            'firstDay' => 1, // La semana empieza el Lunes
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,timeGridDay',
            ],
            'initialView' => 'dayGridMonth', // Vista semanal por defecto (como tu imagen)
            'slotMinTime' => '07:00:00',     // Hora inicio visual
            'slotMaxTime' => '21:00:00',     // Hora fin visual
            'allDaySlot' => false,           // Ocultar fila "Todo el día"
            'nowIndicator' => true,          // Línea roja de la hora actual
        ];
    }
}
