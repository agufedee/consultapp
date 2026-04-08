<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Measurement extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'appointment_id',
        'measurement_date',
        'weight',
        'height',
        'bmi',
        'waist',
    ];

    protected $casts = [
        'measurement_date' => 'datetime',
        'weight' => 'float',
        'height' => 'float',
        'bmi' => 'float',
        'waist' => 'float',
    ];

    // Relaciones
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    // Calcular BMI automáticamente
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($measurement) {
            if ($measurement->weight && $measurement->height) {
                $heightInMeters = $measurement->height / 100;
                $measurement->bmi = round($measurement->weight / ($heightInMeters ** 2), 2);
            }
        });
    }

    // Scopes
    public function scopeLatestMeasurement($query)
    {
        return $query->orderBy('measurement_date', 'desc');
    }

    public function scopeForPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }
}
