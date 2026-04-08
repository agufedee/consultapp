<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case AGENDADO = 'Agendado';
    case CONFIRMADO = 'Confirmado';
    case ATENDIDO = 'Atendido';
    case CANCELADO = 'Cancelado';
    case AUSENTE = 'Ausente';

    /**
     * Obtiene el color para badges de Filament
     */
    public function getColor(): string
    {
        return match ($this) {
            self::AGENDADO => 'gray',
            self::CONFIRMADO => 'info',
            self::ATENDIDO => 'success',
            self::CANCELADO => 'danger',
            self::AUSENTE => 'warning',
        };
    }

    /**
     * Obtiene el label en español
     */
    public function getLabel(): string
    {
        return $this->value;
    }

    /**
     * Obtiene todos los estados como array para select
     */
    public static function toArray(): array
    {
        return array_column(self::cases(), 'value', 'value');
    }

    /**
     * Obtiene un caso desde el string del nombre del status
     */
    public static function fromName(string $name): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->value === $name) {
                return $case;
            }
        }

        return null;
    }
}
