<?php

namespace App\Filament\Resources\Appointments;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Rules\NoOverlappingAppointments;
use CodeWithDennis\FilamentLucideIcons\Enums\LucideIcon;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static string|\BackedEnum|null $navigationIcon = LucideIcon::CalendarDays;

    protected static ?string $navigationLabel = 'Agenda / Turnos';

    protected static ?string $modelLabel = 'Turno';

    protected static ?string $pluralModelLabel = 'Turnos';

    public static function canViewAny(): bool
    {
        return Gate::allows('viewAny', Appointment::class);
    }

    public static function canCreate(): bool
    {
        return Gate::allows('create', Appointment::class);
    }

    public static function canEdit(mixed $record): bool
    {
        return Gate::allows('update', $record);
    }

    public static function canDelete(mixed $record): bool
    {
        return Gate::allows('delete', $record);
    }

    public static function canDeleteAny(): bool
    {
        return Gate::allows('delete', Appointment::class);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([

                // === SECCIÓN IZQUIERDA (Principal) ===
                Section::make('Información del Turno')
                    ->columnSpan(2)
                    ->components([

                        Select::make('patient_id')
                            ->label('Paciente')
                            ->required()

    // 1. RELACIÓN: Conecta con el modelo Patient y precarga personalData
                            ->relationship('patient', modifyQueryUsing: fn ($query) => $query->with('personalData'))

    // 2. ETIQUETA: Usa tu accessor 'full_name' para que se vea bonito
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)

    // 3. BÚSQUEDA: Habilitamos búsqueda por columnas de la relación (dot notation)
                            ->searchable(['personalData.first_name', 'personalData.last_name', 'personalData.dni'])

    // 4. PRELOAD: ¡La clave! Carga los primeros 50 registros apenas abres el select
                            ->preload()
                            ->columnSpanFull(),

                        Grid::make(2)->schema([
                            Select::make('reason')
                                ->label('Motivo')
                                ->options([
                                    'Consulta Inicial' => 'Consulta Inicial',
                                    'Control' => 'Control / Seguimiento',
                                    'Urgencia' => 'Urgencia',
                                ])
                                ->required(),

                            Select::make('status')
                                ->label('Estado')
                                ->options(AppointmentStatus::toArray())
                                ->default(AppointmentStatus::AGENDADO->value)
                                ->required()
                                ->selectablePlaceholder(false)
                                ->native(false),
                        ]),

                        Grid::make(2)->schema([
                            DateTimePicker::make('start_date')
                                ->label('Inicio')
                                ->seconds(false)
                                ->minutesStep(15)
                                ->default(now()->addHour()->startOfHour())
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    if ($state) {
                                        $set('end_date', Carbon::parse($state)->addHour());

                                        $start = Carbon::parse($state);
                                        $hour = $start->hour;

                                        // Validar franja horaria (07:00 - 21:00)
                                        if ($hour < 7 || $hour >= 21) {
                                            $set('start_date', null);
                                            $set('end_date', null);

                                            Notification::make()
                                                ->title('Horario fuera de rango')
                                                ->body('Los turnos deben agendarse entre las 07:00 y las 21:00.')
                                                ->danger()
                                                ->send();

                                            return;
                                        }
                                    }

                                    // Validar superposición en tiempo real
                                    $userId = $get('user_id');
                                    $endDate = $get('end_date');
                                    if ($userId && $state && $endDate) {
                                        $start = Carbon::parse($state);
                                        $end = Carbon::parse($endDate);

                                        $overlapping = Appointment::query()
                                            ->where('user_id', $userId)
                                            ->where(function ($query) use ($start, $end) {
                                                $query->where(function ($q) use ($start) {
                                                    $q->where('start_date', '<=', $start)
                                                        ->where('end_date', '>', $start);
                                                })
                                                    ->orWhere(function ($q) use ($end) {
                                                        $q->where('start_date', '<', $end)
                                                            ->where('end_date', '>=', $end);
                                                    })
                                                    ->orWhere(function ($q) use ($start, $end) {
                                                        $q->where('start_date', '>=', $start)
                                                            ->where('end_date', '<=', $end);
                                                    });
                                            })
                                            ->exists();

                                        if ($overlapping) {
                                            $set('start_date', null);
                                            $set('end_date', null);

                                            Notification::make()
                                                ->title('Turno Superpuesto')
                                                ->body('El profesional ya tiene un turno en ese horario. Por favor seleccioná otro horario.')
                                                ->danger()
                                                ->send();
                                        }
                                    }
                                }),

                            DateTimePicker::make('end_date')
                                ->label('Fin')
                                ->seconds(false)
                                ->minutesStep(15)
                                ->required()
                                ->afterOrEqual('start_date'),
                        ]),
                    ]),

                Section::make('Profesional y Ajustes')
                    ->columnSpan(1)
                    ->components([
                        Select::make('user_id')
                            ->label('Nutricionista')
                            ->relationship('user', 'name')
                            ->default(fn () => \Illuminate\Support\Facades\Auth::id())
                            ->required(),

                        Textarea::make('cancellation_reason')
                            ->label('Motivo Cancelación')
                            ->placeholder('Solo si se cancela...')
                            ->rows(3),

                        // Campo oculto para pasar el ID del registro actual (para edición)
                        \Filament\Forms\Components\Hidden::make('exclude_appointment_id')
                            ->default(fn (?Appointment $record) => $record?->id),

                        TextInput::make('created_at')
                            ->label('Creado')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn (?Appointment $record) => $record?->created_at?->format('d/m/Y H:i') ?? '-'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['patient.personalData', 'user']))
            ->columns([
                Tables\Columns\TextColumn::make('patient_full_name') // Nombre arbitrario
                    ->label('Paciente')
                    // Usamos getStateUsing para forzar el uso del Accesor 'full_name'
                    ->getStateUsing(fn (Appointment $record) => $record->patient?->full_name ?? 'Sin datos')
                    // Búsqueda personalizada: Buscamos dentro de la relación anidada
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('patient', function (Builder $q) use ($search) {
                            $q->whereHas('personalData', function (Builder $q2) use ($search) {
                                $q2->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%");
                            });
                        });
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof AppointmentStatus ? $state->value : (string) $state)
                    ->color(fn ($state): string => AppointmentStatus::fromName($state instanceof AppointmentStatus ? $state->value : (string) $state)?->getColor() ?? 'gray'),
            ])
            ->defaultSort('start_date', 'desc')
            ->filters([])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppointments::route('/'),
            'create' => Pages\CreateAppointment::route('/create'),
            'edit' => Pages\EditAppointment::route('/{record}/edit'),
        ];
    }
}
