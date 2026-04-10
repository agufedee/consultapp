<?php

namespace App\Filament\Widgets;

use App\Enums\AppointmentStatus;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Patients\PatientResource;
use App\Models\Appointment;
use App\Models\Measurement;
use CodeWithDennis\FilamentLucideIcons\Enums\LucideIcon;
use Filament\Forms;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Arr;

class TodayAppointmentsWidget extends BaseWidget
{
    protected static bool $isLazy = true;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Appointment::query()
                    ->with(['patient.personalData'])
                    ->whereDate('start_date', today())
                    ->orderBy('start_date', 'asc')
            )
            ->heading('Turnos para Hoy, '.now()->translatedFormat('l d \d\e F'))
            ->columns([
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Hora')
                    ->dateTime('H:i')
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('patient.full_name')
                    ->label('Paciente')
                    ->searchable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Motivo')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof AppointmentStatus ? $state->value : (string) $state)
                    ->color(fn ($state): string => AppointmentStatus::fromName($state instanceof AppointmentStatus ? $state->value : (string) $state)?->getColor() ?? 'gray'),
            ])
            ->actions([
                // Acción principal: Iniciar Consulta
                \Filament\Actions\Action::make('iniciar_consulta')
                    ->label('Iniciar Consulta')
                    ->icon(LucideIcon::Stethoscope)
                    ->color('success')
                    ->visible(fn (Appointment $record) => in_array($record->status?->value, [AppointmentStatus::AGENDADO->value, AppointmentStatus::CONFIRMADO->value])
                    )
                    ->modalHeading(fn (Appointment $record) => 'Consulta: '.$record->patient?->full_name
                    )
                    ->modalDescription('Complete los datos de la consulta. El estado del turno cambiará a "Atendido".')
                    ->modalWidth('5xl')
                    ->modalSubmitActionLabel('Guardar y Finalizar Consulta')
                    ->mountUsing(function ($form, Appointment $record) {
                        $noteData = $record->clinicalNote?->toArray() ?? [];
                        $measurementData = Measurement::where('appointment_id', $record->id)->first()?->toArray() ?? [];
                        $form->fill(array_merge($noteData, $measurementData));
                    })
                    ->form([
                        Section::make('Notas Clínicas')
                            ->icon(LucideIcon::ClipboardList)
                            ->collapsible()
                            ->schema([
                                Forms\Components\TextInput::make('diagnosis')
                                    ->label('Diagnóstico')
                                    ->placeholder('Ej: Sobrepeso grado I...')
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('observations')
                                    ->label('Evolución / Observaciones')
                                    ->placeholder('Paciente reporta...')
                                    ->rows(4),

                                Forms\Components\Textarea::make('instructions')
                                    ->label('Plan / Indicaciones')
                                    ->placeholder('Pautas alimentarias...')
                                    ->rows(3),
                            ]),

                        Section::make('Mediciones Antropométricas')
                            ->icon(LucideIcon::Scale)
                            ->collapsible()
                            ->collapsed()
                            ->schema([
                                Grid::make(3)->schema([
                                    Forms\Components\TextInput::make('weight')
                                        ->label('Peso (kg)')
                                        ->numeric()
                                        ->suffix('kg'),

                                    Forms\Components\TextInput::make('height')
                                        ->label('Altura (cm)')
                                        ->numeric()
                                        ->suffix('cm'),

                                    Forms\Components\TextInput::make('waist')
                                        ->label('Cintura (cm)')
                                        ->numeric()
                                        ->suffix('cm'),
                                ]),
                            ]),
                    ])
                    ->action(function (Appointment $record, array $data): void {
                        // 1. Cambiar estado a "Atendido"
                        $record->update(['status' => AppointmentStatus::ATENDIDO->value]);

                        // 2. Guardar Nota Clínica
                        if (! empty($data['diagnosis']) || ! empty($data['observations']) || ! empty($data['instructions'])) {
                            $record->clinicalNote()->updateOrCreate(
                                ['appointment_id' => $record->id],
                                Arr::only($data, ['diagnosis', 'observations', 'instructions'])
                            );
                        }

                        // 3. Guardar Medición
                        if (! empty($data['weight']) || ! empty($data['height']) || ! empty($data['waist'])) {
                            Measurement::updateOrCreate(
                                ['appointment_id' => $record->id],
                                [
                                    'patient_id' => $record->patient_id,
                                    'measurement_date' => now(),
                                    'weight' => $data['weight'],
                                    'height' => $data['height'],
                                    'waist' => $data['waist'],
                                ]
                            );
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Consulta Finalizada')
                            ->body('El turno ha sido marcado como Atendido.')
                            ->success()
                            ->send();
                    }),

                // Ver ficha del paciente
                \Filament\Actions\Action::make('ver_paciente')
                    ->label('Ficha')
                    ->icon(LucideIcon::User)
                    ->color('gray')
                    ->url(fn (Appointment $record) => PatientResource::getUrl('view', ['record' => $record->patient_id])),

                // Editar turno
                \Filament\Actions\Action::make('gestionar')
                    ->label('Editar')
                    ->icon(LucideIcon::SquarePen)
                    ->color('gray')
                    ->url(fn (Appointment $record) => AppointmentResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated(false);
    }
}
