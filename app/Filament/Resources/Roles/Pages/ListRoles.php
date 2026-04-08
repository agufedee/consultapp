<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use CodeWithDennis\FilamentLucideIcons\Enums\LucideIcon;

class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    protected static ?string $title = 'Roles del Sistema';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nuevo Rol')
                ->icon(LucideIcon::ShieldPlus),
        ];
    }
}
