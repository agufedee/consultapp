<?php

namespace App\Filament\Resources\Patients\RelationManagers;

use CodeWithDennis\FilamentLucideIcons\Enums\LucideIcon;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ClinicalNotesRelationManager extends RelationManager
{
    protected static string $relationship = 'clinicalNotes';

    protected static ?string $title = 'Historia Clínica';

    protected static string|\BackedEnum|null $icon = LucideIcon::ClipboardList;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Nota Clínica')
                    ->description('Registro de la consulta médica')
                    ->icon(LucideIcon::ClipboardList)
                    ->components([
                        Forms\Components\Select::make('appointment_id')
                            ->label('Turno Asociado')
                            ->relationship(
                                'appointment',
                                'id',
                                fn ($query, $get, $livewire) => $query
                                    ->whereHas('patient', fn ($q) => $q->where('id', $livewire->ownerRecord->id))
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->start_date->format('d/m/Y H:i').' - '.$record->reason
                            )
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('diagnosis')
                            ->label('Diagnóstico')
                            ->placeholder('Ej: Sobrepeso grado I, Déficit de vitamina D...')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('observations')
                            ->label('Evolución / Observaciones')
                            ->placeholder('Paciente reporta mejoría en nivel de energía, refiere adherencia al plan...')
                            ->rows(5)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('instructions')
                            ->label('Plan e Indicaciones')
                            ->placeholder('Mantener plan alimentario, incrementar hidratación, control en 2 semanas...')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('diagnosis')
            ->columns([
                Tables\Columns\TextColumn::make('appointment.start_date')
                    ->label('Fecha de Consulta')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('appointment.reason')
                    ->label('Motivo')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('diagnosis')
                    ->label('Diagnóstico')
                    ->limit(50)
                    ->searchable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('observations')
                    ->label('Observaciones')
                    ->limit(60)
                    ->toggleable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('last_6_months')
                    ->label('Últimos 6 meses')
                    ->query(fn ($query) => $query->whereHas('appointment', fn ($q) => $q->where('start_date', '>=', now()->subMonths(6))
                    )),
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make()
                    ->label('Nueva Nota Clínica')
                    ->icon(LucideIcon::Plus),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('appointment.start_date', 'desc')
            ->emptyStateHeading('Sin notas clínicas registradas')
            ->emptyStateDescription('Comienza a documentar las consultas del paciente')
            ->emptyStateIcon(LucideIcon::ClipboardList);
    }
}
