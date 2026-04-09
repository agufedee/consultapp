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
}
