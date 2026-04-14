<?php

namespace App\Filament\Resources\Patients\RelationManagers;

use App\Enums\AppointmentStatus;
use App\Filament\Actions\ClinicalNotesAction;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Appointment;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

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

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof AppointmentStatus ? $state->value : (string) $state)
                    ->color(fn ($state): string => AppointmentStatus::fromName($state instanceof AppointmentStatus ? $state->value : (string) $state)?->getColor() ?? 'gray'),
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

                \Filament\Actions\DeleteAction::make()->label('Eliminar'),

                ClinicalNotesAction::make(),
            ])
            ->defaultSort('start_date', 'desc');
    }
}
