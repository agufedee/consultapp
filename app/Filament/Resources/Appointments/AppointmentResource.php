<?php

namespace App\Filament\Resources\Appointments;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use CodeWithDennis\FilamentLucideIcons\Enums\LucideIcon;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static string|\BackedEnum|null $navigationIcon = LucideIcon::CalendarDays;

    protected static ?string $navigationLabel = 'Agenda / Turnos';

    protected static ?string $modelLabel = 'Turno';

    protected static ?string $pluralModelLabel = 'Turnos';

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

                            Select::make('status_id')
                                ->label('Estado')
                                ->options(\App\Models\Status::all()->pluck('status_name', 'id'))
                                ->default(fn () => \App\Models\Status::first()?->id)
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
                                ->reactive()
                                ->afterStateUpdated(function ($set, $state) {
                                    if ($state) {
                                        $set('end_date', Carbon::parse($state)->addHour());
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
                            ->default(fn () => Auth::id())
                            ->required(),

                        Textarea::make('cancellation_reason')
                            ->label('Motivo Cancelación')
                            ->placeholder('Solo si se cancela...')
                            ->rows(3),

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
            ->modifyQueryUsing(fn ($query) => $query->with(['patient.personalData', 'status', 'user']))
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

                Tables\Columns\TextColumn::make('status.status_name')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => AppointmentStatus::fromName($state)?->getColor() ?? 'gray'),
            ])
            ->defaultSort('start_date', 'desc')
            ->filters([])
            ->actions([
                EditAction::make(),
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
