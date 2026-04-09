<?php

namespace App\Filament\Resources\Patients;

use App\Models\Gender;
use App\Models\Patient;
use BackedEnum;
use CodeWithDennis\FilamentLucideIcons\Enums\LucideIcon;
use Filament\Forms;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;

class PatientResource extends Resource
{
    protected static ?string $model = Patient::class;

    protected static string|BackedEnum|null $navigationIcon = LucideIcon::Users;

    protected static ?string $navigationLabel = 'Pacientes';

    protected static ?string $modelLabel = 'Paciente';

    protected static ?string $pluralModelLabel = 'Pacientes';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información Personal')
                    ->components([
                        Forms\Components\TextInput::make('first_name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('last_name')
                            ->label('Apellido')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('dni')
                            ->label('DNI')
                            ->required()
                            ->unique(
                                table: 'personal_data',
                                column: 'dni',
                                modifyRuleUsing: function (\Illuminate\Validation\Rules\Unique $rule, $record) {
                                    // Si hay un registro (estamos editando) y tiene ID de datos personales...
                                    if ($record && $record->personal_data_id) {
                                        // ...le decimos a la regla Unique que ignore ESE ID específico
                                        return $rule->ignore($record->personal_data_id);
                                    }

                                    return $rule;
                                }
                            )
                            ->maxLength(20),

                        Forms\Components\DatePicker::make('birth_date')
                            ->label('Fecha de Nacimiento')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->maxDate(now()),

                        Forms\Components\Select::make('gender_id')
                            ->label('Género')
                            ->options(Gender::pluck('name', 'id'))
                            ->required()
                            ->preload(),

                        Forms\Components\Textarea::make('address')
                            ->label('Dirección')
                            ->maxLength(255)
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Información de Contacto')
                    ->components([
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(20),
                    ])
                    ->columns(2),

                Section::make('Estado')
                    ->components([
                        Forms\Components\Toggle::make('active')
                            ->label('Activo')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                return $query
                    ->with(['personalData', 'gender'])
                    ->addSelect([
                        'last_appointment_date' => \App\Models\Appointment::select('start_date')
                            ->whereColumn('patient_id', 'patients.id')
                            ->latest('start_date')
                            ->limit(1),
                    ]);
            })
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nombre Completo')
                    ->searchable(['personalData.first_name', 'personalData.last_name'])
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono')
                    ->default('—'),

                // Fecha de nacimiento (sin default, formateamos manualmente)
                Tables\Columns\TextColumn::make('personalData.birth_date')
                    ->label('Fecha de Nacimiento')
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        return blank($state)
                            ? '—'
                            : ($state instanceof \DateTimeInterface
                                ? $state->format('d/m/Y')
                                : \Carbon\Carbon::parse($state)->format('d/m/Y'));
                    }),

                // Último turno (usando subquery para optimizar rendimiento)
                Tables\Columns\TextColumn::make('last_appointment_date')
                    ->label('Último Turno')
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        return blank($state)
                            ? '—'
                            : ($state instanceof \DateTimeInterface
                                ? $state->format('d/m/Y')
                                : \Carbon\Carbon::parse($state)->format('d/m/Y'));
                    }),

                Tables\Columns\TextColumn::make('active')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Activo' : 'Inactivo')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('active')
                    ->label('Estado')
                    ->options([
                        '1' => 'Activos',
                        '0' => 'Inactivos',
                    ])
                    ->default('1'),
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make()
                    ->label('Ver Ficha')
                    ->icon(LucideIcon::Eye)
                    ->url(fn (Patient $record): string => PatientResource::getUrl('view', ['record' => $record]))
                    ->button()
                    ->color('gray'),

                \Filament\Actions\EditAction::make(),
            ])
            ->groupedBulkActions([
                \Filament\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('Buscar por nombre, apellido, email o Teléfono...')
            ->emptyStateHeading('No hay pacientes registrados')
            ->emptyStateDescription('Crea un nuevo paciente para comenzar.')
            ->emptyStateIcon(LucideIcon::Users);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Grid::make(4)->schema([
                            Group::make([
                                ImageEntry::make('avatar')
                                    ->hiddenLabel()
                                    ->state(fn (Patient $record) => 'https://ui-avatars.com/api/?name='.urlencode($record->full_name).'&color=FFFFFF&background=09090b&size=128')
                                    ->circular(),

                                TextEntry::make('full_name')
                                    ->hiddenLabel()
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->description(fn (Patient $record) => "Paciente registrado hace {$record->created_at->diffForHumans()}"),
                            ])->columnSpan(3),

                            TextEntry::make('active')
                                ->hiddenLabel()
                                ->badge()
                                ->alignEnd()
                                ->color(fn ($state) => $state ? 'success' : 'gray')
                                ->icon(fn ($state) => $state ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle')
                                ->formatStateUsing(fn ($state) => $state ? 'Activo' : 'Inactivo')
                                ->columnSpan(1),
                        ]),

                        Group::make()->schema([])->extraAttributes(['class' => 'border-t border-gray-100 my-4']),

                        Grid::make(3)->schema([
                            TextEntry::make('phone')
                                ->label('Teléfono')
                                ->icon('heroicon-m-phone')
                                ->iconColor('primary')
                                ->placeholder('-')
                                ->state(fn ($record) => $record->phone),

                            TextEntry::make('email')
                                ->label('Email')
                                ->icon('heroicon-m-envelope')
                                ->iconColor('primary')
                                ->placeholder('-')
                                ->state(fn ($record) => $record->email),

                            TextEntry::make('personalData.birth_date')
                                ->label('Fecha de Nacimiento')
                                ->icon('heroicon-m-calendar')
                                ->iconColor('primary')
                                ->placeholder('-')
                                ->formatStateUsing(fn ($state, $record) => $state
                                    ? $state->format('d/m/Y').' ('.$record->personalData->birth_date->age.' años)'
                                    : '-'
                                ),
                        ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AppointmentsRelationManager::class,
            RelationManagers\MeasurementsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPatients::route('/'),
            'create' => Pages\CreatePatient::route('/create'),
            'edit' => Pages\EditPatient::route('/{record}/edit'),
            'view' => Pages\ViewPatient::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('active', true)->count();
    }
}
