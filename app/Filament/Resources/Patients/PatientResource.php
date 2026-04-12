<?php

namespace App\Filament\Resources\Patients;

use App\Models\Gender;
use App\Models\Patient;
use BackedEnum;
use Carbon\Carbon;
use CodeWithDennis\FilamentLucideIcons\Enums\LucideIcon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
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
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
            ->schema([
                Section::make('Información Personal')
                    ->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(100)
                            ->columnSpan('full')
                            ->placeholder('Ingrese el nombre del paciente'),

                        Forms\Components\TextInput::make('last_name')
                            ->label('Apellido')
                            ->required()
                            ->maxLength(100)
                            ->columnSpan('full')
                            ->placeholder('Ingrese el apellido del paciente'),

                        Forms\Components\TextInput::make('dni')
                            ->label('DNI')
                            ->required()
                            ->unique(
                                table: 'personal_data',
                                column: 'dni',
                                ignorable: fn (?Patient $record) => $record?->personalData,
                                ignoreRecord: false
                            )
                            ->maxLength(20)
                            ->placeholder('Ej: 12345678')
                            ->columnSpan('half'),

                        Forms\Components\DatePicker::make('birth_date')
                            ->label('Fecha de Nacimiento')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->maxDate(now())
                            ->columnSpan('half'),

                        Forms\Components\Select::make('gender_id')
                            ->label('Género')
                            ->options(fn () => Gender::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->preload()
                            ->columnSpan('half'),

                        Forms\Components\Textarea::make('address')
                            ->label('Dirección')
                            ->maxLength(255)
                            ->rows(2)
                            ->columnSpanFull()
                            ->placeholder('Ingrese la dirección completa'),
                    ])
                    ->columns(2),

                Section::make('Información de Contacto')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(100)
                            ->columnSpan('half')
                            ->placeholder('ejemplo@correo.com'),

                        Forms\Components\TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(20)
                            ->columnSpan('half')
                            ->placeholder('+54 11 XXXX-XXXX'),
                    ])
                    ->columns(2),

                Section::make('Estado')
                    ->schema([
                        Forms\Components\Toggle::make('active')
                            ->label('Paciente Activo')
                            ->default(true)
                            ->inline(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                return $query
                    ->with(['personalData'])
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
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->weight(FontWeight::Medium),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono')
                    ->default('—')
                    ->copyable()
                    ->copyableState(fn (string $state): string => $state === '—' ? '' : $state),

                Tables\Columns\TextColumn::make('personalData.birth_date')
                    ->label('Fecha de Nacimiento')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('d/m/Y') : '—'),

                Tables\Columns\TextColumn::make('last_appointment_date')
                    ->label('Último Turno')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('d/m/Y') : '—'),

                Tables\Columns\IconColumn::make('active')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Estado')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos')
                    ->queries(
                        true: fn (Builder $query) => $query->where('active', true),
                        false: fn (Builder $query) => $query->where('active', false),
                    )
                    ->default(true),
            ])
            ->actions([
                ViewAction::make()
                    ->icon('heroicon-o-eye')
                    ->url(fn (Patient $record): string => static::getUrl('view', ['record' => $record])),
                EditAction::make()
                    ->icon('heroicon-o-pencil')
                    ->url(fn (Patient $record): string => static::getUrl('edit', ['record' => $record])),
            ], position: RecordActionsPosition::BeforeColumns)
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                CreateAction::make()
                    ->url(fn (): string => static::getUrl('create')),
            ])
            ->striped()
            ->paginated([10, 25, 50]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Ficha del Paciente')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Grid::make(3)->schema([
                            // Columna 1: Avatar
                            ImageEntry::make('avatar')
                                ->hiddenLabel()
                                ->defaultImageUrl(fn (Patient $record): string => 'https://ui-avatars.com/api/?name='.urlencode($record->full_name).'&background=random')
                                ->circular()
                                ->height(80)
                                ->width(80),

                            // Columna 2: Nombre y fecha de registro
                            Group::make([
                                TextEntry::make('full_name')
                                    ->hiddenLabel()
                                    ->weight(FontWeight::Bold)
                                    ->size('lg'),
                                TextEntry::make('created_at')
                                    ->hiddenLabel()
                                    ->color('gray')
                                    ->formatStateUsing(fn ($state) => 'Paciente desde '.$state->diffForHumans()),
                            ]),

                            // Columna 3: Badge de Estado (alineado a la derecha)
                            TextEntry::make('active')
                                ->hiddenLabel()
                                ->badge()
                                ->formatStateUsing(fn (bool $state): string => $state ? 'Activo' : 'Inactivo')
                                ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                                ->alignEnd(),
                        ])->columns(3),

                        // Separador visual
                        Group::make()
                            ->schema([])
                            ->extraAttributes(['class' => 'border-t border-gray-200 dark:border-gray-700 my-4']),

                        // Datos de contacto
                        Grid::make(3)->schema([
                            TextEntry::make('phone')
                                ->label('Teléfono')
                                ->icon('heroicon-m-phone')
                                ->default('—')
                                ->copyable(),

                            TextEntry::make('email')
                                ->label('Email')
                                ->icon('heroicon-m-envelope')
                                ->default('—')
                                ->url(fn (?string $state): ?string => $state ? "mailto:{$state}" : null)
                                ->openUrlInNewTab(),

                            TextEntry::make('personalData.birth_date')
                                ->label('Fecha de Nacimiento')
                                ->icon('heroicon-m-calendar')
                                ->formatStateUsing(fn ($state) => $state ? $state->format('d/m/Y').' ('.$state->age.' años)' : '—'),
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
