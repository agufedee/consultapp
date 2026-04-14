<?php

namespace App\Filament\Widgets;

use App\Enums\AppointmentStatus;
use App\Filament\Actions\ClinicalNotesAction;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Patients\PatientResource;
use App\Models\Appointment;
use CodeWithDennis\FilamentLucideIcons\Enums\LucideIcon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

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
                ClinicalNotesAction::iniciarConsulta(),

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
