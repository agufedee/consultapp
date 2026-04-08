<?php

namespace App\Filament\Resources\Patients\RelationManagers;

use CodeWithDennis\FilamentLucideIcons\Enums\LucideIcon;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class MeasurementsRelationManager extends RelationManager
{
    protected static string $relationship = 'measurements';

    protected static ?string $title = 'Historial de Mediciones';

    protected static string|\BackedEnum|null $icon = LucideIcon::Scale;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Mediciones Antropométricas')
                    ->description('Registra las medidas del paciente para seguimiento')
                    ->icon(LucideIcon::Scale)
                    ->components([
                        Grid::make(3)->schema([
                            Forms\Components\TextInput::make('weight')
                                ->label('Peso (kg)')
                                ->numeric()
                                ->suffix('kg')
                                ->minValue(0)
                                ->maxValue(500)
                                ->step(0.1)
                                ->required(),

                            Forms\Components\TextInput::make('height')
                                ->label('Altura (cm)')
                                ->numeric()
                                ->suffix('cm')
                                ->minValue(0)
                                ->maxValue(300)
                                ->step(0.1)
                                ->required(),

                            Forms\Components\TextInput::make('waist')
                                ->label('Cintura (cm)')
                                ->numeric()
                                ->suffix('cm')
                                ->minValue(0)
                                ->maxValue(300)
                                ->step(0.1),
                        ]),

                        Grid::make(2)->schema([
                            Forms\Components\DateTimePicker::make('measurement_date')
                                ->label('Fecha de Medición')
                                ->default(now())
                                ->native(false)
                                ->displayFormat('d/m/Y H:i')
                                ->required(),

                            Forms\Components\Select::make('appointment_id')
                                ->label('Turno Asociado (opcional)')
                                ->relationship(
                                    'appointment',
                                    'id',
                                    fn ($query, $get, $livewire) => $query
                                        ->where('patient_id', $livewire->ownerRecord->id)
                                        ->with(['status'])
                                )
                                ->getOptionLabelFromRecordUsing(fn ($record) => $record->start_date->format('d/m/Y H:i').' - '.$record->status->status_name
                                )
                                ->searchable()
                                ->preload(),
                        ]),

                        Forms\Components\Placeholder::make('bmi_display')
                            ->label('IMC Calculado')
                            ->content(function ($get) {
                                $weight = $get('weight');
                                $height = $get('height');

                                if ($weight && $height) {
                                    $heightInMeters = $height / 100;
                                    $bmi = round($weight / ($heightInMeters ** 2), 2);

                                    $category = match (true) {
                                        $bmi < 18.5 => 'Bajo peso',
                                        $bmi < 25 => 'Normal',
                                        $bmi < 30 => 'Sobrepeso',
                                        $bmi < 35 => 'Obesidad Grado I',
                                        $bmi < 40 => 'Obesidad Grado II',
                                        default => 'Obesidad Grado III'
                                    };

                                    return "{$bmi} ({$category})";
                                }

                                return 'Ingrese peso y altura para calcular';
                            }),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('measurement_date')
            ->columns([
                Tables\Columns\TextColumn::make('measurement_date')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('weight')
                    ->label('Peso')
                    ->formatStateUsing(fn ($state) => $state ? "{$state} kg" : '-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('height')
                    ->label('Altura')
                    ->formatStateUsing(fn ($state) => $state ? "{$state} cm" : '-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('bmi')
                    ->label('IMC')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) : '-')
                    ->color(fn ($state) => match (true) {
                        ! $state => 'gray',
                        $state < 18.5 => 'warning',
                        $state < 25 => 'success',
                        $state < 30 => 'info',
                        default => 'danger'
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('waist')
                    ->label('Cintura')
                    ->formatStateUsing(fn ($state) => $state ? "{$state} cm" : '-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('appointment.start_date')
                    ->label('Turno Asociado')
                    ->dateTime('d/m/Y')
                    ->toggleable()
                    ->placeholder('Sin turno asociado'),
            ])
            ->filters([
                Tables\Filters\Filter::make('last_3_months')
                    ->label('Últimos 3 meses')
                    ->query(fn ($query) => $query->where('measurement_date', '>=', now()->subMonths(3))),
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make()
                    ->label('Nueva Medición')
                    ->icon(LucideIcon::Plus)
                    ->mutateFormDataUsing(function (array $data, $livewire) {
                        $data['patient_id'] = $livewire->ownerRecord->id;

                        return $data;
                    }),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('measurement_date', 'desc')
            ->emptyStateHeading('Sin mediciones registradas')
            ->emptyStateDescription('Comienza a registrar las mediciones del paciente')
            ->emptyStateIcon(LucideIcon::Scale);
    }
}
