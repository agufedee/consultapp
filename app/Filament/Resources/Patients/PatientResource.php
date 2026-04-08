<?php

namespace App\Filament\Resources\Patients;

use App\Models\Gender;
use App\Models\Patient;
use BackedEnum;
use CodeWithDennis\FilamentLucideIcons\Enums\LucideIcon;
use Filament\Forms;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

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
            ->modifyQueryUsing(fn ($query) => $query->with(['personalData', 'gender']))
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

                // Último turno (obtenemos el estado y lo formateamos)
                Tables\Columns\TextColumn::make('last_appointment')
                    ->label('Último Turno')
                    ->sortable()
                    ->getStateUsing(
                        fn (Patient $record) => $record->appointments()
                            ->latest('start_date')
                            ->value('start_date')
                    )
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
                // Usamos una Sección que ocupe TODO el ancho
                Section::make('Ficha del Paciente')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        // Nivel 1: Encabezado (Foto/Nombre y Estado)
                        Grid::make(4)->schema([
                            Group::make([
                                Placeholder::make('full_name')
                                    ->hiddenLabel()
                                    ->content(fn (Patient $record) => new HtmlString(
                                        "<div class='flex items-center gap-4'>
                                        <div class='h-16 w-16 rounded-full bg-gray-200 flex items-center justify-center text-2xl font-bold text-gray-500'>
                                            ".substr($record->first_name, 0, 1).substr($record->last_name, 0, 1)."
                                        </div>
                                        <div>
                                            <h2 class='text-2xl font-bold'>{$record->full_name}</h2>
                                            <p class='text-sm text-gray-500'>Paciente registrado hace {$record->created_at->diffForHumans()}</p>
                                        </div>
                                    </div>"
                                    )),
                            ])->columnSpan(3), // Ocupa 3/4 del ancho

                            Placeholder::make('active')
                                ->hiddenLabel()
                                ->content(fn (Patient $record) => new HtmlString(
                                    $record->active
                                        ? '<div class="flex justify-end"><span class="px-3 py-1 rounded-full bg-green-50 text-green-700 text-sm font-bold border border-green-200">● Activo</span></div>'
                                        : '<div class="flex justify-end"><span class="px-3 py-1 rounded-full bg-gray-50 text-gray-600 text-sm font-bold border border-gray-200">○ Inactivo</span></div>'
                                ))->columnSpan(1), // Ocupa 1/4 (a la derecha)
                        ]),

                        // Separador visual
                        Group::make()->schema([])->extraAttributes(['class' => 'border-t border-gray-100 my-4']),

                        // Nivel 2: Datos en 3 columnas (aprovechando el ancho)
                        Grid::make(3)->schema([
                            Placeholder::make('phone')
                                ->label('Teléfono')
                                ->icon('heroicon-m-phone')
                                ->content(fn ($record) => $record->phone ?? '-'),

                            Placeholder::make('email')
                                ->label('Email')
                                ->icon('heroicon-m-envelope')
                                ->content(fn ($record) => $record->email ?? '-'),

                            Placeholder::make('personalData.birth_date')
                                ->label('Fecha de Nacimiento')
                                ->icon('heroicon-m-calendar')
                                ->content(fn ($record) => $record->personalData?->birth_date
                                    ? $record->personalData->birth_date->format('d/m/Y').' ('.$record->personalData->birth_date->age.' años)'
                                    : '-'),
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
