<?php

namespace App\Filament\Resources\Patients\RelationManagers;

use App\Enums\AppointmentStatus;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Appointment;
use App\Models\Measurement;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Arr;

class AppointmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'appointments';

    protected static ?string $title = 'Historial de Turnos';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-calendar';

    public function form(Schema $schema): Schema
    {
        // Reutilizamos el Schema del Recurso de Turnos
        return AppointmentResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reason')
            ->columns([
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_time_visual')
                    ->label('Hora')
                // Le decimos: "Aunque me llamo 'start_time_visual', saca el dato de 'start_date'"
                    ->getStateUsing(fn ($record) => $record->start_date)
                    ->date('H:i')
                    ->sortable(query: function ($query, string $direction) {
                        // Ordenamos usando la columna real de la DB
                        return $query->orderBy('start_date', $direction);
                    }),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Motivo'),

                Tables\Columns\TextColumn::make('status.status_name')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => AppointmentStatus::fromName($state)?->getColor() ?? 'gray'),
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make()
                    ->label('Agendar Turno'),
                // Opcional: si quieres modal grande
                // ->slideOver()
                // ->modalWidth('5xl'),
            ])
            ->actions([
                \Filament\Actions\EditAction::make()->label('Editar'),

                Action::make('notes')
                    ->label('Notas Clinicas')
                    ->icon('heroicon-o-clipboard-document-list')
                    // Color: Verde si tiene nota O medición, Gris si está vacío
                    ->color(fn (Appointment $record) => ($record->clinicalNote || $record->measurement()->exists()) ? 'success' : 'gray')
                    ->modalHeading('Seguimiento')
                    ->modalWidth('5xl') // Un poco más ancho para que entren bien las mediciones
                    ->modalSubmitActionLabel('Guardar')
                    // 1. CARGAR DATOS (Merge de Nota + Medición)
                    ->mountUsing(function ($form, Appointment $record) {
                        // Datos de la nota clínica
                        $noteData = $record->clinicalNote?->toArray() ?? [];

                        // Datos de la medición asociada a este turno (si existe)
                        // Asumimos que hay una medición por turno.
                        $measurementData = Measurement::where('appointment_id', $record->id)->first()?->toArray() ?? [];

                        // Fusionamos ambos arrays para llenar el formulario
                        $form->fill(array_merge($noteData, $measurementData));
                    })

                    // 2. FORMULARIO UNIFICADO
                    ->form([
                        // --- SECCIÓN 1: NOTAS CLÍNICAS ---
                        Grid::make(1)->schema([
                            Forms\Components\TextInput::make('diagnosis')
                                ->label('Diagnóstico')
                                ->placeholder('Ej: Sobrespeso grado I...')
                                ->columnSpanFull(),

                            Forms\Components\Textarea::make('observations')
                                ->label('Evolución / Subjetivo')
                                ->placeholder('Paciente reporta...')
                                ->rows(4),

                            Forms\Components\Textarea::make('instructions')
                                ->label('Plan / Indicaciones')
                                ->placeholder('Pautas alimentarias...')
                                ->rows(3),
                        ]),

                        // --- SECCIÓN 2: MEDICIONES ANTROPOMÉTRICAS ---
                        Section::make('Mediciones de la Sesión')
                            ->icon('heroicon-o-scale')
                            ->compact()
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
                            ])->collapsible(),
                    ])

                    // 3. GUARDAR EN DOS TABLAS
                    ->action(function (Appointment $record, array $data): void {
                        // A) Guardar Nota Clínica
                        $record->clinicalNote()->updateOrCreate(
                            ['appointment_id' => $record->id],
                            Arr::only($data, ['diagnosis', 'observations', 'instructions'])
                        );

                        // B) Guardar Medición (Solo si se ingresó algún dato numérico)
                        if (! empty($data['weight']) || ! empty($data['height']) || ! empty($data['waist'])) {
                            Measurement::updateOrCreate(
                                ['appointment_id' => $record->id], // Busca por ID de turno
                                [
                                    'patient_id' => $record->patient_id, // Dato obligatorio
                                    'measurement_date' => now(),         // Fecha actual
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
                    }),
            ])
            ->defaultSort('start_date', 'desc');
    }
}



