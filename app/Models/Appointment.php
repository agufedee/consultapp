<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'patient_id',
        'user_id',
        'send_reminder',
        'reason',
        'start_date',
        'end_date',
        'status',
        'cancellation_reason',
    ];

    protected $casts = [
        'send_reminder' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'status' => AppointmentStatus::class,
    ];

    // Business Rules validations
    protected static function booted(): void
    {
        static::creating(function (Appointment $appointment) {
            // BR-001: Cannot be in the past
            if ($appointment->start_date->isPast()) {
                throw new \InvalidArgumentException('No se pueden agendar turnos en el pasado.');
            }

            // BR-003: Must be within business hours (7:00 - 21:00)
            $hour = $appointment->start_date->hour;
            if ($hour < 7 || $hour >= 21) {
                throw new \InvalidArgumentException('Los turnos deben agendarse entre las 07:00 y las 21:00.');
            }

            // BR-004: Cannot create appointment for inactive patient
            $appointment->loadMissing('patient');
            if ($appointment->patient && ! $appointment->patient->active) {
                throw new \InvalidArgumentException('No se pueden agendar turnos para pacientes inactivos.');
            }
        });
    }

    // Relaciones
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function clinicalNote(): HasOne
    {
        return $this->hasOne(ClinicalNote::class, 'appointment_id');
    }

    public function measurement(): HasOne
    {
        return $this->hasOne(Measurement::class);
    }

    // Scopes
    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>=', now())
            ->orderBy('start_date');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('start_date', today());
    }

    public function scopeByStatus($query, $statusId)
    {
        return $query->where('status', $statusId);
    }

    /**
     * Check if this appointment overlaps with another for the same user.
     */
    public function overlapsWithUser(int $userId, ?int $excludeId = null): bool
    {
        return static::where('user_id', $userId)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('start_date', '<=', $this->start_date)
                        ->where('end_date', '>', $this->start_date);
                })
                    ->orWhere(function ($q) {
                        $q->where('start_date', '<', $this->end_date)
                            ->where('end_date', '>=', $this->end_date);
                    })
                    ->orWhere(function ($q) {
                        $q->where('start_date', '>=', $this->start_date)
                            ->where('end_date', '<=', $this->end_date);
                    });
            })
            ->exists();
    }
}
