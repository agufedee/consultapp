<?php

namespace App\Filament\Resources\Patients\Pages;

use App\Enums\AppointmentStatus;
use App\Filament\Resources\Patients\PatientResource;
use App\Models\Appointment;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Facades\Auth;

class ViewPatient extends ViewRecord
{
    protected static string $resource = PatientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),

            // --- CORRECCIÓN: Usamos Action genérica, no CreateAction ---
            \Filament\Actions\Action::make('agendarTurno')
                ->label('Agendar Turno')
                ->icon('heroicon-o-calendar')
                ->color('primary')
                ->modalHeading('Nuevo Turno para este Paciente')
                ->modalWidth('2xl')
                ->modalSubmitActionLabel('Agendar')

                // 1. EL FORMULARIO
                ->form([
                    Grid::make(2)->schema([
                        DateTimePicker::make('start_date')
                            ->label('Inicio')
                            ->seconds(false)
                            ->minutesStep(15)
                            ->default(now()->addHour()->startOfHour())
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn ($set, $state) => $state ? $set('end_date', \Carbon\Carbon::parse($state)->addHour()) : null),

                        DateTimePicker::make('end_date')
                            ->label('Fin')
                            ->seconds(false)
                            ->minutesStep(15)
                            ->required(),
                    ]),

                    Select::make('reason')
                        ->label('Motivo')
                        ->options([
                            'Consulta Inicial' => 'Consulta Inicial',
                            'Control' => 'Control / Seguimiento',
                            'Urgencia' => 'Urgencia',
                        ])
                        ->required(),

                    Select::make('user_id')
                        ->label('Profesional')
                        ->options(\App\Models\User::all()->pluck('name', 'id'))
                        ->default(Auth::id())
                        ->required(),

                ])

                // 2. LA LÓGICA DE GUARDADO MANUAL
                ->action(function (array $data) {
                    // 1. Inyectamos el ID del paciente actual
                    $data['patient_id'] = $this->record->id;

                    // 2. Inyectamos estado inicial como enum
                    $data['status'] = AppointmentStatus::AGENDADO->value;

                    // 3. Creamos el turno
                    Appointment::create($data);

                    Notification::make()
                        ->title('Turno agendado correctamente')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }
}
