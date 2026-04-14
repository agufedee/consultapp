<?php

namespace App\Filament\Actions;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Measurement;
use CodeWithDennis\FilamentLucideIcons\Enums\LucideIcon;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Arr;

class ClinicalNotesAction
{
    public static function make(): Action
    {
        return Action::make('clinical_notes')
            ->label('Notas Clínicas')
            ->icon(LucideIcon::ClipboardList)
            ->color(fn (Appointment $record) => ($record->clinicalNote || $record->measurement()->exists()) ? 'success' : 'gray')
            ->modalWidth('5xl')
            ->modalSubmitActionLabel('Guardar')
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
                // 1. Guardar Nota Clínica
                if (! empty($data['diagnosis']) || ! empty($data['observations']) || ! empty($data['instructions'])) {
                    $record->clinicalNote()->updateOrCreate(
                        ['appointment_id' => $record->id],
                        Arr::only($data, ['diagnosis', 'observations', 'instructions'])
                    );
                }

                // 2. Guardar Medición
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
                    ->title('Notas Clínicas Actualizadas')
                    ->success()
                    ->send();
            });
    }

    public static function iniciarConsulta(): Action
    {
        return Action::make('iniciar_consulta')
            ->label('Iniciar Consulta')
            ->icon(LucideIcon::Stethoscope)
            ->color('success')
            ->visible(fn (Appointment $record) => in_array($record->status?->value, [AppointmentStatus::AGENDADO->value, AppointmentStatus::CONFIRMADO->value]))
            ->modalHeading(fn (Appointment $record) => 'Consulta: '.$record->patient?->full_name)
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
            });
    }
}
